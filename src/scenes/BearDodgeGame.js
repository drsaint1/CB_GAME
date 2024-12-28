import React, { useRef, useEffect, useState } from "react";
import { Connection, PublicKey, clusterApiUrl } from "@solana/web3.js";
import { AnchorProvider, Program } from "@project-serum/anchor";
import { useWallet } from "@solana/wallet-adapter-react";
import BN from "bn.js";
import idl from "../idl.json";

const BearDodgeGame = () => {
  // Constants and initializations
  const programId = new PublicKey(
    "7o1egHUjWDxEF5YeEdfnBRTsUwtePF31drEU7xZhoZA9"
  );
  const connection = new Connection(clusterApiUrl("devnet"), "processed");
  const wallet = useWallet();
  const provider = new AnchorProvider(
    connection,
    wallet,
    AnchorProvider.defaultOptions()
  );
  const program = new Program(idl, programId, provider);

  // React states and refs
  const canvasRef = useRef(null);
  const [cbEarned, setCbEarned] = useState(0);
  const [gameOver, setGameOver] = useState(false);
  const [walletAddress, setWalletAddress] = useState(null);

  const balls = useRef([{ x: 50, y: 50, vx: 3, vy: 3 }]);
  const bear = useRef({ x: 600, width: 120, height: 70 });
  const keysPressed = useRef({});
  const bearImage = useRef(new Image());

  const canvasWidth = 1200;
  const canvasHeight = 720;

  // Load bear image
  const loadBearImage = () => {
    bearImage.current.src = "/bear.png";
  };

  // Update game logic
  const updateGame = async () => {
    const canvas = canvasRef.current;
    const ctx = canvas.getContext("2d");
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Handle bear movement
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

    // Draw bear
    ctx.drawImage(
      bearImage.current,
      bear.current.x,
      canvas.height / 2 - bear.current.height / 2,
      bear.current.width,
      bear.current.height
    );

    // Update balls
    balls.current.forEach((ball) => {
      ball.x += ball.vx;
      ball.y += ball.vy;

      if (ball.x <= 0 || ball.x >= canvas.width) ball.vx *= -1;
      if (ball.y <= 0 || ball.y >= canvas.height) ball.vy *= -1;

      // Draw ball
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

      // Check collision
      if (
        ball.x > bear.current.x &&
        ball.x < bear.current.x + bear.current.width &&
        ball.y > canvas.height / 2 - bear.current.height / 2 &&
        ball.y < canvas.height / 2 + bear.current.height / 2
      ) {
        setGameOver(true);
      }
    });

    // Update CB earned
    setCbEarned((prev) => prev + 1);

    if (cbEarned % 60 === 0) {
      await earnCb();
    }
  };

  // Earn CB function
  const earnCb = async () => {
    if (!walletAddress) return;

    try {
      const [sessionDataPda] = await PublicKey.findProgramAddress(
        [Buffer.from("session"), new PublicKey(walletAddress).toBuffer()],
        programId
      );
      await program.methods
        .earnCb(new BN(1))
        .accounts({
          sessionData: sessionDataPda,
          player: walletAddress,
        })
        .rpc();
      console.log("CB Earned on-chain!");
    } catch (err) {
      console.error("Error earning CB:", err);
    }
  };

  // Game loop setup
  useEffect(() => {
    loadBearImage();

    if (!gameOver) {
      const canvas = canvasRef.current;
      canvas.width = canvasWidth;
      canvas.height = canvasHeight;

      const gameLoop = setInterval(updateGame, 16);

      const addBallInterval = setInterval(() => {
        balls.current.push({
          x: Math.random() * canvasWidth,
          y: Math.random() * canvasHeight,
          vx: (Math.random() > 0.5 ? 1 : -1) * (2 + Math.random() * 3),
          vy: (Math.random() > 0.5 ? 1 : -1) * (2 + Math.random() * 3),
        });
      }, 10000);

      return () => {
        clearInterval(gameLoop);
        clearInterval(addBallInterval);
      };
    }
  }, [gameOver]);

  return (
    <div>
      {gameOver ? (
        <div className="game-over">
          <h2>Game Over</h2>
          <p>CB Earned: {Math.floor(cbEarned / 60)}</p>
          <button
            onClick={() => {
              setGameOver(false);
              setCbEarned(0);
              balls.current = [{ x: 50, y: 50, vx: 3, vy: 3 }];
            }}
          >
            Restart
          </button>
        </div>
      ) : (
        <div>
          <p>CB Earned: {Math.floor(cbEarned / 60)}</p>
          <canvas ref={canvasRef} className="game-canvas"></canvas>
        </div>
      )}
    </div>
  );
};

export default BearDodgeGame;
