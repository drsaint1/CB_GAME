import React, { useRef, useEffect, useState } from "react";
import axios from "axios";
import '../style/games.css';


// import { Connection, PublicKey, clusterApiUrl, Transaction } from "@solana/web3.js";
// import { Program, AnchorProvider, web3, utils, BN} from "@project-serum/anchor";
// import { TOKEN_PROGRAM_ID } from "@solana/spl-token";
// import idl from "../idl.json"; // Replace with your actual IDL file

function BearDodgeGame({ walletAddress }) {

    const canvasRef = useRef(null);
    const [cbEarned, setCbEarned] = useState(0);
    const [gameOver, setGameOver] = useState(false);
    const [lastSavedPoints, setLastSavedPoints] = useState(0);
    const [countdown, setCountdown] = useState(0);
    
    // const [walletAddress, setWalletAddress] = useState(null);

    // useEffect(() => {
    //     const address = localStorage.getItem("walletAddress");
    //     setWalletAddress(address);
    //   }, []);


    // const programID = new PublicKey(idl.metadata.address); // Program ID
    // // const programId = new PublicKey(idl.address);
    // // const network = "https://api.devnet.solana.com"; // Use Mainnet in production
    // const network = clusterApiUrl("devnet"); // Change to mainnet-beta for production
    // const opts = { preflightCommitment: "processed" };

    // const [connection, setConnection] = useState(null);
    // const [program, setProgram] = useState(null);

    // useEffect(() => {
    //     const connection = new Connection(network, opts.preflightCommitment);
    //     const provider = new AnchorProvider(connection, window.solana, opts);
    //     const program = new Program(idl, programID, provider);

    //     setConnection(connection);
    //     setProgram(program);
    // }, []);




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
            // if (balls.current.length < 4) { // Limit maximum number of balls
                balls.current.push({
                    x: Math.random() * canvasWidth,
                    y: Math.random() * canvasHeight,
                    vx: (Math.random() > 0.5 ? 1 : -1) * (1 + Math.random() * 2), // Slower speed
                    vy: (Math.random() > 0.5 ? 1 : -1) * (1 + Math.random() * 2),
                });
            // }
        }, 10000);

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
        const speed = 5;
        // const speed = 2;
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


    // Handle "Pay to Continue"
    //   const handleContinue = async () => {
    //     if (cbEarned < 100) {
    //       alert("Not enough CB to continue! Earn more points or restart.");
    //       return;
    //     }
    //     try {
    //       const tx = await program.rpc.payToContinue({
    //         accounts: {
    //           playerTokenAccount: walletAddress, // Replace with actual token account
    //           treasuryAccount: new PublicKey("<TREASURY_ACCOUNT_PUBLIC_KEY>"),
    //           playerAuthority: walletAddress,
    //           tokenProgram: TOKEN_PROGRAM_ID,
    //         },
    //       });
    //       console.log("Transaction successful:", tx);
    //       setGameOver(false);
    //     } catch (error) {
    //       console.error("Error in Pay to Continue:", error);
    //     }
    //   };

    // Handle "Purchase Shield"
    const handlePurchaseShield = async () => {
        try {
            const tx = await program.rpc.purchaseShield({
                accounts: {
                    playerTokenAccount: walletAddress,
                    treasuryAccount: new PublicKey("<TREASURY_ACCOUNT_PUBLIC_KEY>"),
                    playerAuthority: walletAddress,
                    tokenProgram: TOKEN_PROGRAM_ID,
                },
            });
            console.log("Shield purchased successfully:", tx);
            alert("Shield purchased!");
        } catch (error) {
            console.error("Error in Purchase Shield:", error);
        }
    };

    // const purchaseShield = async () => {
    //     try {
    //       setStatus("Processing...");
    //       const tx = await program.methods.purchaseShield().accounts({
    //         playerTokenAccount: /* User token account public key */,
    //         treasuryAccount: /* Treasury account public key */,
    //         sessionData: /* Session data public key */,
    //         config: /* Config public key */,
    //         player: wallet.publicKey,
    //         playerAuthority: wallet.publicKey,
    //         tokenProgram: /* Token Program public key */,
    //       }).signers([]).rpc();
      
    //       setStatus(`Transaction Successful: ${tx}`);
      
    //       // Log the transaction in the database
    //       const response = await fetch("http://your-backend-url/api/log-transaction", {
    //         method: "POST",
    //         headers: {
    //           "Content-Type": "application/json",
    //         },
    //         body: JSON.stringify({
    //           user_id: 1, // Replace with the actual user ID
    //           transaction_id: tx,
    //           action: "purchaseShield",
    //           amount: 10, // Replace with the actual amount
    //         }),
    //       });
      
    //       if (!response.ok) {
    //         throw new Error("Failed to log transaction");
    //       }
      
    //       const result = await response.json();
    //       console.log(result.message);
    //     } catch (err) {
    //       console.error(err);
    //       setStatus("Transaction Failed");
    //     }
    //   };
      

    // Handle "Withdraw Tokens"
    const handleWithdrawTokens = async () => {
        try {
            const tx = await program.rpc.withdrawTokens(new web3.BN(cbEarned), {
                accounts: {
                    playerTokenAccount: walletAddress,
                    vaultAccount: new PublicKey("<VAULT_ACCOUNT_PUBLIC_KEY>"),
                    playerAuthority: walletAddress,
                    tokenProgram: TOKEN_PROGRAM_ID,
                },
            });
            console.log("Tokens withdrawn successfully:", tx);
            alert("Tokens withdrawn!");
            setCbEarned(0);
        } catch (error) {
            console.error("Error in Withdraw Tokens:", error);
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

    // Handle "Save Earned CB" (Earn Referral Rewards)
    //   const handleSavePoints = async (points) => {
    //     try {
    //       const tx = await program.rpc.earnCb(new web3.BN(points), {
    //         accounts: {
    //           player: walletAddress,
    //           sessionData: new PublicKey("<SESSION_DATA_PUBLIC_KEY>"),
    //           referrerSessionData: new PublicKey("<REFERRER_SESSION_DATA_PUBLIC_KEY>"),
    //           config: new PublicKey("<CONFIG_PUBLIC_KEY>"),
    //         },
    //       });
    //       console.log("Points saved successfully:", tx);
    //     } catch (error) {
    //       console.error("Error in Save Points:", error);
    //     }
    //   };




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
                        <div aria-hidden="true" className="fixed inset-0 w-full  h-full bg-black/50 cursor-pointer">
                        </div>

                        {/* <!-- Modal --> */}
                        <div className="relative w-full cursor-pointer pointer-events-none transition my-auto p-4">
                            <div
                                className="w-full py-2 bg-white cursor-default pointer-events-auto dark:bg-gray-800 relative rounded-xl mx-auto max-w-lg">
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
                                                className="inline-flex items-center justify-center py-1 gap-1 bg-red-600 hover:bg-red-500 text-white  font-medium rounded-lg border transition-colors outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset dark:focus:ring-offset-0 min-h-[2.25rem] px-4 text-sm border-gray-300  focus:ring-primary-600 focus:text-primary-600 focus:bg-primary-50 focus:border-primary-600 dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-600 dark:hover:border-gray-500 dark:text-gray-200 dark:focus:text-primary-400 dark:focus:border-primary-400 dark:focus:bg-gray-800">
                                                <span className="flex items-center gap-1">
                                                    <span>Restart</span>
                                                </span>
                                            </button>

                                            {/* Continue Button */}
                                            <button
                                                type="submit"
                                                onClick={handleContinue}
                                                className="inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset dark:focus:ring-offset-0 min-h-[2.25rem] px-4 text-sm text-white shadow focus:ring-white border-transparent bg-yellow-600 hover:bg-yellow-500 focus:bg-red-700 focus:ring-offset-red-700">
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
                    <div className="flex flex-col items-center p-4 space-y-4 bg-gray-900  h-screen text-white">
                        <div className="flex gap-2">
                            <button onClick={handlePurchaseShield} className="px-6 py-2 bg-green-600 hover:bg-green-500 text-white font-medium rounded-lg shadow-md transition" > Purchase Shield </button>
                            <button onClick={handleWithdrawTokens} className="px-6 py-2 bg-blue-600 hover:bg-blue-500 text-white font-medium rounded-lg shadow-md transition" > Withdraw Tokens </button>
                        </div>
                        <p className="text-3xl font-semibold text-white">Cb earned: {Math.floor(cbEarned / 60)}</p>
                        <div className="relative w-full max-w-4xl bg-gray-100 border-2 border-gray-300 rounded-lg shadow-lg overflow-hidden ">
                            <canvas ref={canvasRef} className="w-full h-full object-cover" ></canvas>
                        </div>
                    </div>


                )}
        </div>

    );
}

export default BearDodgeGame;
