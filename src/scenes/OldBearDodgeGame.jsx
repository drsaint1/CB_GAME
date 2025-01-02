import React, { useRef, useEffect, useState } from "react";

function BearDodgeGame() {
  const canvasRef = useRef(null);
  const [cbEarned, setCbEarned] = useState(0);
  const [gameOver, setGameOver] = useState(false);

  const balls = useRef([{ x: 50, y: 50, vx: 3, vy: 3 }]);
  const bear = useRef({ x: 600, width: 150, height: 70 }); // Increased size by 30%
  const keysPressed = useRef({ ArrowLeft: false, ArrowRight: false }); // Correct initialization
  const bearImage = useRef(new Image());

  const canvasWidth = 1200;
  const canvasHeight = 720;

  // const loadBearImage = () => {
  //   bearImage.current.src = "/bear.png"; // Path to your bear image
  // };

  const loadBearImage = () => {
    bearImage.current.src = "/bear.png"; // Path to your bear image
    bearImage.current.onload = () => {
      startGame(); // Start the game loop only after the image is loaded
    };
  };




  const updateGame = () => {
    const canvas = canvasRef.current;
    const ctx = canvas.getContext("2d");
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Update Bear position based on keys pressed (Horizontal movement only)
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

    // Draw Bear Image with the increased size
    ctx.drawImage(
      bearImage.current,
      bear.current.x,
      canvas.height / 2 - bear.current.height / 2, // Vertical center
      bear.current.width,
      bear.current.height
    );

    // Update and Draw Balls
    balls.current.forEach((ball) => {
      ball.x += ball.vx;
      ball.y += ball.vy;

      // Bounce balls off walls
      if (ball.x <= 0 || ball.x >= canvas.width) ball.vx *= -1;
      if (ball.y <= 0 || ball.y >= canvas.height) ball.vy *= -1;

      // Draw ball with 3D effect
      ctx.beginPath();

      // Create a radial gradient for 3D shading
      const gradient = ctx.createRadialGradient(
        ball.x - 4, // Offset for highlight
        ball.y - 4, // Offset for highlight
        1, // Inner radius (small highlight area)
        ball.x,
        ball.y,
        15 // Outer radius (entire ball)
      );

      gradient.addColorStop(0, "white"); // Bright highlight
      gradient.addColorStop(0.2, "lightblue"); // Mid-tone
      gradient.addColorStop(0.8, "blue"); // Base color
      gradient.addColorStop(1, "darkblue"); // Shadow

      ctx.fillStyle = gradient;
      ctx.arc(ball.x, ball.y, 10, 0, Math.PI * 2);
      ctx.fill();
      ctx.closePath();

      // Check for collision with Bear
      if (
        ball.x > bear.current.x &&
        ball.x < bear.current.x + bear.current.width &&
        ball.y > canvas.height / 2 - bear.current.height / 2 &&
        ball.y < canvas.height / 2 + bear.current.height / 2
      ) {
        setGameOver(true);
      }
    });

    // Update cbEarned
    setCbEarned((prev) => prev + 1);
  };

  const handleMouseMove = (e) => {
    const canvas = canvasRef.current;
    const rect = canvas.getBoundingClientRect();
    const mouseX = e.clientX - rect.left;

    // Update bear position based on mouse movement (Horizontal only)
    bear.current.x = Math.max(
      0,
      Math.min(
        mouseX - bear.current.width / 2,
        canvas.width - bear.current.width
      )
    );
  };

  const handleKeyDown = (e) => {
    // Only set valid keys and prevent default behavior
    if (e.key === "ArrowLeft" || e.key === "ArrowRight") {
      e.preventDefault(); // Prevent browser scrolling
      keysPressed.current[e.key] = true;
    }
  };

  const handleKeyUp = (e) => {
    // Clear the relevant key state
    if (e.key === "ArrowLeft" || e.key === "ArrowRight") {
      e.preventDefault(); // Prevent browser scrolling
      keysPressed.current[e.key] = false;
    }
  };



  const startGame = () => {
    if (!gameOver) {
      const canvas = canvasRef.current;
      canvas.width = canvasWidth;
      canvas.height = canvasHeight;
  
      const gameLoop = setInterval(updateGame, 16);
  
      // Add event listeners for movement
      window.addEventListener("keydown", handleKeyDown);
      window.addEventListener("keyup", handleKeyUp);
      canvas.addEventListener("mousemove", handleMouseMove);
  
      // Add new balls every 10 seconds
      const addBallInterval = setInterval(() => {
        balls.current.push({
          x: Math.random() * canvasWidth,
          y: Math.random() * canvasHeight,
          vx: (Math.random() > 0.5 ? 1 : -1) * (2 + Math.random() * 3),
          vy: (Math.random() > 0.5 ? 1 : -1) * (2 + Math.random() * 3),
        });
      }, 10000); // Updated to 10 seconds
  
      return () => {
        clearInterval(gameLoop);
        clearInterval(addBallInterval);
        window.removeEventListener("keydown", handleKeyDown);
        window.removeEventListener("keyup", handleKeyUp);
        canvas.removeEventListener("mousemove", handleMouseMove);
      };
    }
  };

  useEffect(() => {
    loadBearImage(); // Load the bear image and start the game after it's loaded
  }, [gameOver]);
  

  // useEffect(() => {
  //   loadBearImage(); // Load the bear image

  //   if (!gameOver) {
  //     const canvas = canvasRef.current;
  //     canvas.width = canvasWidth;
  //     canvas.height = canvasHeight;

  //     const gameLoop = setInterval(updateGame, 16);

  //     // Add event listeners for movement
  //     window.addEventListener("keydown", handleKeyDown);
  //     window.addEventListener("keyup", handleKeyUp);
  //     canvas.addEventListener("mousemove", handleMouseMove);

  //     // Add new balls every 10 seconds
  //     const addBallInterval = setInterval(() => {
  //       balls.current.push({
  //         x: Math.random() * canvasWidth,
  //         y: Math.random() * canvasHeight,
  //         vx: (Math.random() > 0.5 ? 1 : -1) * (2 + Math.random() * 3),
  //         vy: (Math.random() > 0.5 ? 1 : -1) * (2 + Math.random() * 3),
  //       });
  //     }, 10000); // Updated to 10 seconds

  //     return () => {
  //       clearInterval(gameLoop);
  //       clearInterval(addBallInterval);
  //       window.removeEventListener("keydown", handleKeyDown);
  //       window.removeEventListener("keyup", handleKeyUp);
  //       canvas.removeEventListener("mousemove", handleMouseMove);
  //     };
  //   }
  // }, [gameOver]);

  return (
    <div>
      {gameOver ? (
        <div className="game-over">
          <h2>Game Over</h2>
          <p>Cb earned: {Math.floor(cbEarned / 60)}</p>
          <button
            onClick={() => {
              setGameOver(false);
              setCbEarned(0);
              balls.current = [{ x: 50, y: 50, vx: 3, vy: 3 }]; // Reset balls
            }}
          >
            Restart
          </button>
        </div>
      ) : (
        <div>
          <p>Cb earned: {Math.floor(cbEarned / 60)}</p>
          <canvas ref={canvasRef} className="game-canvas"></canvas>
        </div>
      )}
    </div>
  );
}

export default BearDodgeGame;
