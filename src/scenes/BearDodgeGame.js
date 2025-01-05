import React, { useRef, useEffect, useState } from "react";

function BearDodgeGame() {
  const canvasRef = useRef(null);
  const [cbEarned, setCbEarned] = useState(0);
  const [gameOver, setGameOver] = useState(false);

  const balls = useRef([{ x: 50, y: 50, vx: 3, vy: 3, radius: 10 }]);
  const bear = useRef({ x: 600, width: 150, height: 70 });
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

  const loadAssets = () => {
    bearImage.current.src = "/bear.png";
    backgroundImage.current.src = "/background.png";
    canvasBackgroundImage.current.src = "/canvas-background.jpg";

    if (!backgroundSound.current) {
      backgroundSound.current = new Audio("/background.mp3");
      backgroundSound.current.loop = true;
    }

    if (!collisionSound.current) {
      collisionSound.current = new Audio("/collision.mp3");
    }
  };

  const resizeGame = () => {
    const scaleFactor = Math.min(
      window.innerWidth / baseCanvasWidth,
      window.innerHeight / baseCanvasHeight
    );
    setCanvasWidth(baseCanvasWidth * scaleFactor);
    setCanvasHeight(baseCanvasHeight * scaleFactor);

    // Adjust bear and ball size
    bear.current.width = 150 * scaleFactor;
    bear.current.height = 70 * scaleFactor;
    balls.current.forEach((ball) => {
      ball.radius = 10 * scaleFactor;
    });

    // Adjust the canvas container padding to center the canvas
    const canvasContainer = document.getElementById("canvas-container");
    if (canvasContainer) {
      const leftPadding = (window.innerWidth - canvasWidth) / 2;
      canvasContainer.style.paddingLeft = `${leftPadding}px`;
      canvasContainer.style.paddingRight = `${leftPadding}px`;
    }
  };

  const updateGame = () => {
    const canvas = canvasRef.current;
    const ctx = canvas.getContext("2d");
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Draw Canvas Background
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

    // Update Bear position based on keys or touch
    const speed = 5 * (canvasWidth / baseCanvasWidth);
    if (keysPressed.current["ArrowLeft"] && bear.current.x > 0) {
      bear.current.x -= speed;
    }
    if (
      keysPressed.current["ArrowRight"] &&
      bear.current.x < canvas.width - bear.current.width
    ) {
      bear.current.x += speed;
    }

    // Draw Bear
    if (bearImage.current.complete && bearImage.current.naturalWidth > 0) {
      ctx.drawImage(
        bearImage.current,
        bear.current.x,
        canvas.height / 2 - bear.current.height / 2,
        bear.current.width,
        bear.current.height
      );
    }

    // Update and Draw Balls with the requested gradient
    balls.current.forEach((ball) => {
      ball.x += ball.vx;
      ball.y += ball.vy;

      // Bounce balls off walls
      if (ball.x <= 0 || ball.x >= canvas.width) ball.vx *= -1;
      if (ball.y <= 0 || ball.y >= canvas.height) ball.vy *= -1;

      // Calculate gradient based on screen size
      const gradient = ctx.createRadialGradient(
        ball.x,
        ball.y,
        0, // Inner radius
        ball.x,
        ball.y,
        ball.radius // Outer radius
      );

      // Explicit gradient stops that don't scale based on the ball's radius
      gradient.addColorStop(0, "white");
      gradient.addColorStop(0.2, "lightblue");
      gradient.addColorStop(0.8, "blue");
      gradient.addColorStop(1, "darkblue");

      // Apply the gradient
      ctx.beginPath();
      ctx.fillStyle = gradient;
      ctx.arc(ball.x, ball.y, ball.radius, 0, Math.PI * 2);
      ctx.fill();
      ctx.closePath();

      // Check collision with Bear
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

    // Update score
    setCbEarned((prev) => prev + 1);
  };

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

  const handleTouchMove = (e) => {
    const canvas = canvasRef.current;
    const rect = canvas.getBoundingClientRect();
    const touchX = e.touches[0].clientX - rect.left;

    bear.current.x = Math.max(
      0,
      Math.min(
        touchX - bear.current.width / 2,
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

  useEffect(() => {
    loadAssets();
    resizeGame();
    window.addEventListener("resize", resizeGame);

    if (backgroundSound.current && backgroundSound.current.paused) {
      backgroundSound.current.play().catch((err) => {
        console.error("Failed to play background music:", err);
      });
    }

    if (!gameOver) {
      const canvas = canvasRef.current;
      canvas.width = canvasWidth;
      canvas.height = canvasHeight;

      const gameLoop = setInterval(updateGame, 16);

      // Add event listeners
      window.addEventListener("keydown", handleKeyDown);
      window.addEventListener("keyup", handleKeyUp);
      canvas.addEventListener("mousemove", handleMouseMove);
      canvas.addEventListener("touchmove", handleTouchMove);

      const addBallInterval = setInterval(() => {
        balls.current.push({
          x: Math.random() * canvasWidth,
          y: Math.random() * canvasHeight,
          vx: (Math.random() > 0.5 ? 1 : -1) * (2 + Math.random() * 3),
          vy: (Math.random() > 0.5 ? 1 : -1) * (2 + Math.random() * 3),
          radius: 10 * (canvasWidth / baseCanvasWidth),
        });
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

  return (
    <div style={{ backgroundImage: `url(${backgroundImage.current.src})` }}>
      {gameOver ? (
        <div className="game-over">
          <h2>Game Over</h2>
          <p>Cb earned: {Math.floor(cbEarned / 60)}</p>
          <button
            onClick={() => {
              setGameOver(false);
              setCbEarned(0);
              balls.current = [{ x: 50, y: 50, vx: 3, vy: 3, radius: 10 }];
            }}
          >
            Restart
          </button>
        </div>
      ) : (
        <div>
          <p>Cb earned: {Math.floor(cbEarned / 60)}</p>
          <div id="canvas-container">
            <canvas ref={canvasRef} className="game-canvas"></canvas>
          </div>
        </div>
      )}
    </div>
  );
}

export default BearDodgeGame;
