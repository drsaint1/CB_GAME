import React, { useRef, useEffect, useState } from "react";
import axios from "axios";
// import '../style/games.css';

function BearDodgeGame({ walletAddress }) {
   
    const canvasRef = useRef(null);
    const [cbEarned, setCbEarned] = useState(0);
    const [gameOver, setGameOver] = useState(false);
    const [lastSavedPoints, setLastSavedPoints] = useState(0);
    const [countdown, setCountdown] = useState(0); // New countdown state
    // const [walletAddress, setWalletAddress] = useState(null);

    // useEffect(() => {
    //     const address = localStorage.getItem("walletAddress");
    //     setWalletAddress(address);
    //   }, []);

    const balls = useRef([{ x: 50, y: 50, vx: 3, vy: 3 }]);
    const bear = useRef({ x: 600, width: 150, height: 70 }); // Increased size by 30%
    const keysPressed = useRef({ ArrowLeft: false, ArrowRight: false }); // Correct initialization
    const bearImage = useRef(new Image());

    const canvasWidth = 1200;
    const canvasHeight = 720;

    const loadBearImage = () => {
        bearImage.current.src = "/bear.png"; // Path to your bear image
        bearImage.current.onload = () => {
            console.log("Bear image loaded");
            startGame(); // Start the game loop only after the image is loaded
        };
    };

    const startGame = () => {
        const canvas = canvasRef.current;

        // Check if canvas exists before accessing its properties
        if (!canvas) {
            console.error("Canvas is not yet rendered");
            return;
        }

        canvas.width = canvasWidth;
        canvas.height = canvasHeight;

        const gameLoop = setInterval(updateGame, 16);

        // Add event listeners for movement
        window.addEventListener("keydown", handleKeyDown);
        window.addEventListener("keyup", handleKeyUp);
        canvas.addEventListener("mousemove", handleMouseMove);

        // Add new balls every 10 seconds
        const addBallInterval = setInterval(() => {
            if (balls.current.length < 4) { // Limit maximum number of balls
                balls.current.push({
                    x: Math.random() * canvasWidth,
                    y: Math.random() * canvasHeight,
                    vx: (Math.random() > 0.5 ? 1 : -1) * (1 + Math.random() * 2), // Slower speed
                    vy: (Math.random() > 0.5 ? 1 : -1) * (1 + Math.random() * 2),
                });
            }
        }, 200000);

        return () => {
            clearInterval(gameLoop);
            clearInterval(addBallInterval);
            window.removeEventListener("keydown", handleKeyDown);
            window.removeEventListener("keyup", handleKeyUp);
            canvas.removeEventListener("mousemove", handleMouseMove);
        };
    };

    const updateGame = () => {
        const canvas = canvasRef.current;

        // Ensure canvas exists before proceeding
        if (!canvas) return;

        const ctx = canvas.getContext("2d");
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Update Bear position based on keys pressed (Horizontal movement only)
        // const speed = 5;
        const speed = 2;
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

    const handleSavePoints = async (points) => {
        try {
            // const walletAddress = "user-wallet-address"; // Replace with dynamic wallet address
            const response = await axios.post("http://127.0.0.1:8000/api/save-points", {
                wallet_address: walletAddress,
                points,
            });
            console.log("Points saved successfully:", response.data);
        } catch (error) {
            console.error("Error saving points:", error);
        }
    };

    //  const handleContinue = () => {
    //     let timer = 5; // 5-second countdown
    //     setCountdown(timer);

    //     const countdownInterval = setInterval(() => {
    //         timer -= 1;
    //         setCountdown(timer);

    //         if (timer === 0) {
    //             clearInterval(countdownInterval);
    //             setCountdown(0);
    //             setGameOver(false); // Resume game
    //         }
    //     }, 1000);
    // };

    // const handleRestart = () => {
    //     setGameOver(false);
    //     setCbEarned(0); // Reset points
    //     balls.current = [{ x: 50, y: 50, vx: 3, vy: 3 }]; // Reset balls
    // };

    const handleContinue = async () => {
        const newPoints = Math.floor(cbEarned / 60) - lastSavedPoints; // Calculate additional points earned
        setLastSavedPoints((prev) => prev + newPoints); // Update last saved points
        await handleSavePoints(newPoints); // Save additional points
        setGameOver(false);
    };

    const handleRestart = async () => {
        const totalPoints = Math.floor(cbEarned / 60); // Get total earned points
        await handleSavePoints(totalPoints); // Save all points
        setCbEarned(0); // Reset points
        setLastSavedPoints(0); // Reset last saved points
        setGameOver(false);
        balls.current = [{ x: 50, y: 50, vx: 3, vy: 3 }]; // Reset balls
    };

    useEffect(() => {
        loadBearImage();
    }, []);

    useEffect(() => {
        if (!gameOver) {
            // Delay starting the game until the DOM is ready
            requestAnimationFrame(() => {
                if (canvasRef.current) startGame();
            });
        }
    }, [gameOver]);

  
  
    

    
    return (
        <div>
             {
            //  countdown > 0 ? (
            //     <div className="countdown">
            //         <h1>Game will continue in {countdown}...</h1>
            //     </div>
            // ) :
             gameOver ? (
                <div className="fixed inset-0 z-40 min-h-full overflow-y-auto overflow-x-hidden transition flex items-center">
                    {/* <!-- overlay --> */}
                    <div aria-hidden="true" className="fixed inset-0 w-full h-full bg-black/50 cursor-pointer">
                    </div>

                    {/* <!-- Modal --> */}
                    <div className="relative w-full cursor-pointer pointer-events-none transition my-auto p-4">
                        <div
                            className="w-full py-2 bg-white cursor-default pointer-events-auto dark:bg-gray-800 relative rounded-xl mx-auto max-w-sm">
                            <div className="space-y-2 p-2">
                                <div className="p-4 space-y-2 text-center dark:text-white">
                                    <h2 className="text-xl font-bold tracking-tight" id="page-action.heading"> Game Over </h2>
                                    <p className="text-gray-500"> Do you want to continue or restart this game </p>
                                    <p className="font-medium">Cb earned: {Math.floor(cbEarned / 60)}</p>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <div aria-hidden="true" className="border-t dark:border-gray-700 px-2"></div>

                                <div className="px-6 py-2">
                                    <div className="grid gap-2 grid-cols-[repeat(auto-fit,minmax(0,1fr))]">
                                        <button
                                            type="button"
                                          
                                            onClick={handleRestart}
                                            className="inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset dark:focus:ring-offset-0 min-h-[2.25rem] px-4 text-sm text-gray-800 bg-white border-gray-300 hover:bg-gray-50 focus:ring-primary-600 focus:text-primary-600 focus:bg-primary-50 focus:border-primary-600 dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-600 dark:hover:border-gray-500 dark:text-gray-200 dark:focus:text-primary-400 dark:focus:border-primary-400 dark:focus:bg-gray-800">
                                            <span className="flex items-center gap-1">
                                                <span>Restart</span>
                                            </span>
                                        </button>

                                        {/* Continue Button */}
                                        <button
                                            type="submit"
                                            onClick={handleContinue}
                                            className="inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset dark:focus:ring-offset-0 min-h-[2.25rem] px-4 text-sm text-white shadow focus:ring-white border-transparent bg-red-600 hover:bg-red-500 focus:bg-red-700 focus:ring-offset-red-700">
                                            <span className="flex items-center gap-1">
                                                <span>Pay 100CB to Continue</span>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>
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
