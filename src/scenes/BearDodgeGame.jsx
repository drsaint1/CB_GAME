import React, { useRef, useEffect, useState, useMemo } from "react";
import "../style/games.css";
import {
  useInitializeSession,
  usePurchaseShield,
  useWithdrawTokens,
  useSavePoints,
  useContinueGame,
  initializeProgram,
  useInitializeConfig,
  useCheckBalance,
} from "../hooks/useContracts";
import { useWallet } from "@solana/wallet-adapter-react";

function BearDodgeGame() {
  const canvasRef = useRef(null);
  const [cbEarned, setCbEarned] = useState(0);
  const [gameOver, setGameOver] = useState(false);
  const [sessionPDA, setSessionPDA] = useState(null);
  const [configPDA, setConfigPDA] = useState(null);
  const [errorMessage, setErrorMessage] = useState("");

  // Bear / game references
  const balls = useRef([{ x: 50, y: 50, vx: 3, vy: 3 }]);
  const bear = useRef({ x: 600, width: 150, height: 70 });
  const keysPressed = useRef({ ArrowLeft: false, ArrowRight: false });
  const bearImage = useRef(new Image());
  const backgroundImage = useRef(new Image());
  const backgroundSound = useRef(null);
  const collisionSound = useRef(null);

  // Canvas sizing
  const baseCanvasWidth = 1200;
  const baseCanvasHeight = 720;
  const [canvasWidth, setCanvasWidth] = useState(baseCanvasWidth);
  const [canvasHeight, setCanvasHeight] = useState(baseCanvasHeight);
  const [programState, setProgramState] = useState(null);

  const hasAskedRef = useRef(false);
  const { wallet } = useWallet();

  const program = useMemo(() => {
    if (wallet) {
      return initializeProgram(wallet);
    }
    return null;
  }, [wallet]);
  const { initializeSession } = useInitializeSession(program);
  const { initializeConfig } = useInitializeConfig(program);
  const { purchaseShield } = usePurchaseShield(program);
  const { continueGame } = useContinueGame(program);
  const { savePoints } = useSavePoints(program);
  const { checkBalance } = useCheckBalance(program);
  useEffect(() => {
    if (program) {
      setProgramState(program);
      console.log(program);
    }
  }, []);

  // Prompt to initialize session after program is ready
  useEffect(() => {
    if (!sessionPDA && !hasAskedRef.current) {
      hasAskedRef.current = true;

      if (window.confirm("Do you want to initialize your session now?")) {
        initializeSession()
          .then((pda) => {
            initializeConfig(10, 2, 5).then((config) => {
              setSessionPDA(pda);
              checkBalance(pda, "checkBalance init");
              setConfigPDA(config);
            });
          })
          .catch((err) => {
            console.error("Error initializing session:", err);
            setErrorMessage("Error initializing session.");
          });
      }
    }
  }, [sessionPDA]);

  // Load assets on mount
  useEffect(() => {
    bearImage.current.src = "/bear.png";
    backgroundImage.current.src = "/background.png";

    if (!backgroundSound.current) {
      backgroundSound.current = new Audio("/background.mp3");
      backgroundSound.current.loop = true;
    }
    if (!collisionSound.current) {
      collisionSound.current = new Audio("/collision.mp3");
    }

    bearImage.current.onload = () => {
      startGame();
    };
  }, []);

  // Resize logic
  const resizeGame = () => {
    const scaleFactor = Math.min(
      window.innerWidth / baseCanvasWidth,
      window.innerHeight / baseCanvasHeight
    );
    setCanvasWidth(baseCanvasWidth * scaleFactor);
    setCanvasHeight(baseCanvasHeight * scaleFactor);
    bear.current.width = 150 * scaleFactor;
    bear.current.height = 70 * scaleFactor;
    balls.current.forEach((ball) => {
      ball.radius = 10 * scaleFactor;
    });
    bear.current.x = (baseCanvasWidth * scaleFactor - bear.current.width) / 2;
  };

  // Start the main game loop
  const startGame = () => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    canvas.width = canvasWidth;
    canvas.height = canvasHeight;

    const gameLoop = setInterval(updateGame, 16);
    window.addEventListener("keydown", handleKeyDown);
    window.addEventListener("keyup", handleKeyUp);
    canvas.addEventListener("mousemove", handleMouseMove);

    // Periodically add new balls
    const addBallInterval = setInterval(() => {
      balls.current.push({
        x: Math.random() * canvasWidth,
        y: Math.random() * canvasHeight,
        vx: (Math.random() > 0.5 ? 1 : -1) * (1 + Math.random() * 2),
        vy: (Math.random() > 0.5 ? 1 : -1) * (1 + Math.random() * 2),
      });
    }, 10000);

    return () => {
      clearInterval(gameLoop);
      clearInterval(addBallInterval);
      window.removeEventListener("keydown", handleKeyDown);
      window.removeEventListener("keyup", handleKeyUp);
      canvas.removeEventListener("mousemove", handleMouseMove);
    };
  };

  // Update loop
  const updateGame = () => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext("2d");
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    const speed = 5;
    if (keysPressed.current["ArrowLeft"] && bear.current.x > 0) {
      bear.current.x -= speed;
    }
    if (
      keysPressed.current["ArrowRight"] &&
      bear.current.x < canvas.width - bear.current.width
    ) {
      bear.current.x += speed;
    }

    // Draw the bear
    ctx.drawImage(
      bearImage.current,
      bear.current.x,
      canvas.height / 2 - bear.current.height / 2,
      bear.current.width,
      bear.current.height
    );

    // Move & draw balls
    balls.current.forEach((ball) => {
      ball.x += ball.vx;
      ball.y += ball.vy;
      if (ball.x <= 0 || ball.x >= canvas.width) ball.vx *= -1;
      if (ball.y <= 0 || ball.y >= canvas.height) ball.vy *= -1;

      ctx.beginPath();
      const gradient = ctx.createRadialGradient(
        ball.x - 4,
        ball.y - 4,
        1,
        ball.x,
        ball.y,
        15
      );
      gradient.addColorStop(0, "white");
      gradient.addColorStop(0.2, "lightblue");
      gradient.addColorStop(0.8, "blue");
      gradient.addColorStop(1, "darkblue");
      ctx.fillStyle = gradient;
      ctx.arc(ball.x, ball.y, 10, 0, Math.PI * 2);
      ctx.fill();
      ctx.closePath();

      // Collision detection
      if (
        ball.x > bear.current.x &&
        ball.x < bear.current.x + bear.current.width &&
        ball.y > canvas.height / 2 - bear.current.height / 2 &&
        ball.y < canvas.height / 2 + bear.current.height / 2
      ) {
        collisionSound.current.play();
        setGameOver(true);
      }
    });

    // Increment CB tokens over time
    setCbEarned((prev) => prev + 1);
  };

  // Event handlers
  const handleMouseMove = (e) => {
    const canvas = canvasRef.current;
    const rect = canvas.getBoundingClientRect();
    const mouseX = e.clientX - rect.left;
    bear.current.x = Math.max(
      0,
      Math.min(
        mouseX - bear.current.width / 2,
        canvas.width - bear.current.width
      )
    );
  };

  const handleKeyDown = (e) => {
    if (e.key === "ArrowLeft" || e.key === "ArrowRight") {
      e.preventDefault();
      keysPressed.current[e.key] = true;
    }
  };

  const handleKeyUp = (e) => {
    if (e.key === "ArrowLeft" || e.key === "ArrowRight") {
      e.preventDefault();
      keysPressed.current[e.key] = false;
    }
  };

  // Restart the game (save points first)
  const handleRestart = async () => {
    try {
      if (!sessionPDA) {
        alert("Session is not initialized yet.");
        return;
      }
      await savePoints(sessionPDA, Math.floor(cbEarned / 60));
      checkBalance(sessionPDA, "checkBalance continue");
      alert("Points saved on-chain.");
    } catch (err) {
      console.error(err);
      alert("Error saving points.");
    }
    setCbEarned(0);
    setGameOver(false);
    balls.current = [{ x: 50, y: 50, vx: 3, vy: 3 }];
  };

  // Purchase shield
  const handleShieldPurchase = async () => {
    if (!sessionPDA || !configPDA) {
      alert("Session and config must be initialized.");
      return;
    }
    try {
      const txId = await purchaseShield(sessionPDA, configPDA);
      alert("Shield purchased. Tx ID: " + txId);
    } catch (err) {
      console.error(err);
      alert("Error purchasing shield.");
    }
  };

  // Pay to continue
  const handleContinue = async () => {
    if (!sessionPDA || !configPDA) {
      alert("Session or Config is not initialized yet.");
      return;
    }
    try {
      await continueGame(sessionPDA, configPDA);
      alert("Paid 100 CB tokens to continue.");
      setGameOver(false);
    } catch (err) {
      console.error(err);
      alert("Error continuing game.");
    }
  };

  // Resize and background music
  useEffect(() => {
    resizeGame();
    window.addEventListener("resize", resizeGame);
    if (backgroundSound.current && backgroundSound.current.paused) {
      backgroundSound.current.play().catch((err) => {
        console.error("Error playing background music:", err);
      });
    }
    if (!gameOver && canvasRef.current) {
      startGame();
    }

    return () => {
      window.removeEventListener("resize", resizeGame);
    };
  }, [gameOver, canvasWidth, canvasHeight]);

  return (
    <div style={{ backgroundImage: `url(${backgroundImage.current.src})` }}>
      {gameOver ? (
        <div className="fixed inset-0 z-40 flex items-center">
          <div className="fixed inset-0 bg-black/50"></div>
          <div className="relative mx-auto max-w-lg p-4 bg-white rounded-xl">
            <div className="text-center">
              <h2 className="text-xl font-bold">Game Over</h2>
              <p>Do you want to continue or restart this game?</p>
              <p className="font-medium">
                CB earned: {Math.floor(cbEarned / 60)}
              </p>
            </div>
            <div className="grid gap-2 grid-cols-2 mt-4">
              <button
                onClick={handleRestart}
                className="py-1 bg-red-600 text-white rounded"
              >
                Restart
              </button>
              <button
                onClick={handleContinue}
                className="py-1 bg-yellow-600 text-white rounded"
              >
                Pay 100CB to Continue
              </button>
            </div>
            <div className="mt-2 flex gap-2">
              <button
                onClick={handleShieldPurchase}
                className="py-1 bg-green-600 text-white rounded"
              >
                Purchase Shield
              </button>
            </div>
          </div>
        </div>
      ) : (
        <div className="flex flex-col items-center p-4 space-y-4 bg-gray-900 h-screen text-white">
          <div className="flex gap-2">
            <button
              onClick={handleShieldPurchase}
              className="px-6 py-2 bg-green-600 rounded"
            >
              Purchase Shield
            </button>
          </div>
          <p className="text-3xl font-semibold">
            CB earned: {Math.floor(cbEarned / 60)}
          </p>
          <div
            id="canvas-container"
            className="relative w-full max-w-4xl bg-gray-100 border rounded shadow overflow-hidden"
          >
            <canvas
              ref={canvasRef}
              className="w-full h-full object-cover"
            ></canvas>
          </div>
        </div>
      )}
    </div>
  );
}

export default BearDodgeGame;
