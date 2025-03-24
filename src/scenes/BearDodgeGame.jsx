import React, { useRef, useEffect, useState, useMemo } from "react";
import "../style/games.css";
import {
  usePurchaseShield,
  useContinueGame,
  initializeProgram,
  useCreateTransaction,
} from "../hooks/useContracts";
import { useWallet } from "@solana/wallet-adapter-react";
import { toast } from "react-toastify";

function BearDodgeGame({ sessionPDA, configPDA }) {
  const canvasRef = useRef(null);
  const [cbEarned, setCbEarned] = useState(0);
  const [gameOver, setGameOver] = useState(false);
  const [lastSavedScore, setLastSavedScore] = useState(0);

  const balls = useRef([{ x: 50, y: 50, vx: 3, vy: 3, radius: 10 }]);
  const bear = useRef({ x: undefined, width: 150, height: 70 });
  const keysPressed = useRef({ ArrowLeft: false, ArrowRight: false });
  const bearImage = useRef(new Image());
  const backgroundImage = useRef(new Image());
  const canvasBackgroundImage = useRef(new Image());
  const backgroundSound = useRef(null);
  const collisionSound = useRef(null);

  const baseCanvasWidth = 1200;
  const baseCanvasHeight = 720;
  const [canvasWidth, setCanvasWidth] = useState(baseCanvasWidth);
  const [canvasHeight, setCanvasHeight] = useState(baseCanvasHeight);

  const [shieldActive, setShieldActive] = useState(false);
  const [shieldCountdown, setShieldCountdown] = useState(0);
  const shieldActiveRef = useRef(false);

  const [continueInvincible, setContinueInvincible] = useState(false);
  const [invincibleCountdown, setInvincibleCountdown] = useState(0);
  const continueInvincibleRef = useRef(false);

  const { createTransaction } = useCreateTransaction();
  const { wallet } = useWallet();

  const program = useMemo(
    () => (wallet ? initializeProgram(wallet) : null),
    [wallet]
  );
  const { purchaseShield } = usePurchaseShield(program);
  const { continueGame } = useContinueGame(program);

  const saveGamePoints = async (pointsEarned) => {
    const walletData = JSON.parse(localStorage.getItem("walletData"));
    try {
      await fetch(`${import.meta.env.VITE_API_URL}/save-points`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          wallet_address: walletData.walletAddress,
          points: pointsEarned,
        }),
      });
      toast.success("Points saved successfully");
    } catch (error) {
      toast.error("Failed to save points");
    }
  };

  useEffect(() => {
    if (gameOver && cbEarned > lastSavedScore) {
      const pointsToSave = Math.floor((cbEarned - lastSavedScore) / 60);
      if (pointsToSave > 0) {
        saveGamePoints(pointsToSave);
        setLastSavedScore(cbEarned);
      }
    }
  }, [gameOver]);

  useEffect(() => {
    if (shieldActive && shieldCountdown > 0) {
      const timer = setInterval(
        () => setShieldCountdown((prev) => prev - 1),
        1000
      );
      return () => clearInterval(timer);
    } else if (shieldCountdown === 0) {
      setShieldActive(false);
      shieldActiveRef.current = false;
    }
  }, [shieldActive, shieldCountdown]);

  useEffect(() => {
    if (continueInvincible && invincibleCountdown > 0) {
      const timer = setInterval(
        () => setInvincibleCountdown((prev) => prev - 1),
        1000
      );
      return () => clearInterval(timer);
    } else if (invincibleCountdown === 0) {
      setContinueInvincible(false);
      continueInvincibleRef.current = false;
    }
  }, [continueInvincible, invincibleCountdown]);

  const loadAssets = () => {
    bearImage.current.src = "/bear.png";
    backgroundImage.current.src = "/background.png";
    canvasBackgroundImage.current.src = "/canvas-background.jpg";

    backgroundSound.current = new Audio("/background.mp3");
    backgroundSound.current.loop = true;

    collisionSound.current = new Audio("/collision.mp3");
  };

  const resizeGame = () => {
    const scaleFactor = Math.min(
      window.innerWidth / baseCanvasWidth,
      window.innerHeight / baseCanvasHeight
    );
    setCanvasWidth(baseCanvasWidth * scaleFactor);
    setCanvasHeight(baseCanvasHeight * scaleFactor);
    bear.current.width = 150 * scaleFactor;
    bear.current.height = 70 * scaleFactor;
    balls.current.forEach((ball) => (ball.radius = 10 * scaleFactor));
    bear.current.x = (baseCanvasWidth * scaleFactor - bear.current.width) / 2;
  };

  const updateGame = () => {
    const canvas = canvasRef.current;
    const ctx = canvas.getContext("2d");
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (bear.current.x === undefined) {
      bear.current.x = (canvas.width - bear.current.width) / 2;
    }

    if (
      canvasBackgroundImage.current.complete &&
      canvasBackgroundImage.current.naturalWidth > 0
    ) {
      ctx.drawImage(
        canvasBackgroundImage.current,
        0,
        0,
        canvas.width,
        canvas.height
      );
    }

    const speed = 5 * (canvasWidth / baseCanvasWidth);
    if (keysPressed.current["ArrowLeft"] && bear.current.x > 0)
      bear.current.x -= speed;
    if (
      keysPressed.current["ArrowRight"] &&
      bear.current.x < canvas.width - bear.current.width
    )
      bear.current.x += speed;

    if (bearImage.current.complete && bearImage.current.naturalWidth > 0) {
      ctx.drawImage(
        bearImage.current,
        bear.current.x,
        canvas.height / 2 - bear.current.height / 2,
        bear.current.width,
        bear.current.height
      );
    }

    balls.current.forEach((ball) => {
      ball.x += ball.vx;
      ball.y += ball.vy;
      if (ball.x <= 0 || ball.x >= canvas.width) ball.vx *= -1;
      if (ball.y <= 0 || ball.y >= canvas.height) ball.vy *= -1;

      const gradient = ctx.createRadialGradient(
        ball.x,
        ball.y,
        0,
        ball.x,
        ball.y,
        ball.radius
      );
      gradient.addColorStop(0, "white");
      gradient.addColorStop(0.2, "lightblue");
      gradient.addColorStop(0.8, "blue");
      gradient.addColorStop(1, "darkblue");

      ctx.beginPath();
      ctx.fillStyle = gradient;
      ctx.arc(ball.x, ball.y, ball.radius, 0, Math.PI * 2);
      ctx.fill();
      ctx.closePath();

      if (
        !shieldActiveRef.current &&
        !continueInvincibleRef.current &&
        ball.x > bear.current.x &&
        ball.x < bear.current.x + bear.current.width &&
        ball.y > canvas.height / 2 - bear.current.height / 2 &&
        ball.y < canvas.height / 2 + bear.current.height / 2
      ) {
        collisionSound.current.play();
        setGameOver(true);
      }
    });

    setCbEarned((prev) => prev + 1);
  };

  const handleMouseMove = (e) => {
    const rect = canvasRef.current.getBoundingClientRect();
    const mouseX = e.clientX - rect.left;
    bear.current.x = Math.max(
      0,
      Math.min(
        mouseX - bear.current.width / 2,
        canvasWidth - bear.current.width
      )
    );
  };

  const handleTouchMove = (e) => {
    const rect = canvasRef.current.getBoundingClientRect();
    const touchX = e.touches[0].clientX - rect.left;
    bear.current.x = Math.max(
      0,
      Math.min(
        touchX - bear.current.width / 2,
        canvasWidth - bear.current.width
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

  useEffect(() => {
    loadAssets();
    resizeGame();
    window.addEventListener("resize", resizeGame);

    if (backgroundSound.current && backgroundSound.current.paused) {
      backgroundSound.current.play().catch(console.error);
    }

    if (!gameOver) {
      const canvas = canvasRef.current;
      canvas.width = canvasWidth;
      canvas.height = canvasHeight;

      const gameLoop = setInterval(updateGame, 16);

      window.addEventListener("keydown", handleKeyDown);
      window.addEventListener("keyup", handleKeyUp);
      canvas.addEventListener("mousemove", handleMouseMove);
      canvas.addEventListener("touchmove", handleTouchMove);

      const addBallInterval = setInterval(() => {
        const edge = Math.floor(Math.random() * 4);
        let ball = {
          x: 0,
          y: 0,
          vx: (Math.random() > 0.5 ? 1 : -1) * (2 + Math.random() * 3),
          vy: (Math.random() > 0.5 ? 1 : -1) * (2 + Math.random() * 3),
          radius: 10 * (canvasWidth / baseCanvasWidth),
        };
        switch (edge) {
          case 0:
            ball.x = Math.random() * canvasWidth;
            ball.y = 0;
            ball.vy = Math.abs(ball.vy);
            break;
          case 1:
            ball.x = Math.random() * canvasWidth;
            ball.y = canvasHeight;
            ball.vy = -Math.abs(ball.vy);
            break;
          case 2:
            ball.x = 0;
            ball.y = Math.random() * canvasHeight;
            ball.vx = Math.abs(ball.vx);
            break;
          case 3:
            ball.x = canvasWidth;
            ball.y = Math.random() * canvasHeight;
            ball.vx = -Math.abs(ball.vx);
            break;
        }
        balls.current.push(ball);
      }, 10000);

      return () => {
        clearInterval(gameLoop);
        clearInterval(addBallInterval);
        window.removeEventListener("keydown", handleKeyDown);
        window.removeEventListener("keyup", handleKeyUp);
        canvas.removeEventListener("mousemove", handleMouseMove);
        canvas.removeEventListener("touchmove", handleTouchMove);
        window.removeEventListener("resize", resizeGame);
      };
    }
  }, [gameOver, canvasWidth, canvasHeight]);

  const handleShieldPurchase = async () => {
    try {
      const txId = await purchaseShield(sessionPDA, configPDA);
      if (!txId) throw new Error("Transaction failed or was not confirmed.");
      await createTransaction("Shield purchased", 100);
      setShieldActive(true);
      shieldActiveRef.current = true;
      setShieldCountdown(15);
      toast.success("Shield purchased! You are invincible for 15 seconds.");
    } catch (err) {
      console.error(err);
      toast.error(err.message || "Error purchasing shield.");
    }
  };

  const handleContinue = async () => {
    try {
      const txId = await continueGame(sessionPDA, configPDA);
      if (!txId) {
        throw new Error("Continue game transaction failed or not confirmed.");
      }

      await createTransaction("Pay to continue", 100);

      setContinueInvincible(true);
      setInvincibleCountdown(5);
      setGameOver(false);
      toast.success("Paid 100 CB tokens. You are invincible for 5 seconds.");
    } catch (err) {
      console.error(err);
      toast.error(err.message || "Error continuing game.");
    }
  };

  const handleRestart = async () => {
    setCbEarned(0);
    setLastSavedScore(0);
    setGameOver(false);
    balls.current = [{ x: 50, y: 50, vx: 3, vy: 3 }];
  };

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
          {shieldActive && (
            <p className="text-lg text-green-400">
              Shield active: {shieldCountdown}s
            </p>
          )}
          {continueInvincible && (
            <p className="text-lg text-yellow-400">
              Invincible: {invincibleCountdown}s
            </p>
          )}
          <div
            id="canvas-container"
            className="relative w-full max-w-4xl bg-gray-100 border rounded shadow overflow-hidden"
          >
            <canvas
              ref={canvasRef}
              className="w-full h-full object-cover game-canvas"
            ></canvas>
          </div>
        </div>
      )}
    </div>
  );
}

export default BearDodgeGame;
