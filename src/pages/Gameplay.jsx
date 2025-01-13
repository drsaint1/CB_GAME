import React, { useState, useEffect } from "react";
import WalletIntegration from "../components/WalletIntegration";
import BearDodgeGame from "../scenes/BearDodgeGame";
import '../App.css';
import { Link } from "react-router-dom";
// import FaUser


function GamePlay() {
  const [isWalletConnected, setIsWalletConnected] = useState(false);
  const [gameStarted, setGameStarted] = useState(false);
  const [userData, setUserData] = useState(null);

  useEffect(() => {
    // Check localStorage for existing wallet data
    const walletData = JSON.parse(localStorage.getItem("walletData"));
    if (walletData) {
      const { walletAddress, expiresAt } = walletData;
      const currentTime = new Date().getTime();

      if (currentTime < expiresAt) {
        // Wallet is still valid
        setIsWalletConnected(true);
        setUserData({ wallet_address: walletAddress }); // Adjust based on user data structure
      } else {
        // Wallet expired, clear localStorage
        localStorage.removeItem("walletData");
      }
    }
  }, []);

  const backgroundStyle = {
    position: "relative",
    height: "80vh",
    width: "100%",
    overflow: "hidden",
    backgroundImage: "url('../assets/background.jpg')", // Adjust path as needed
    backgroundSize: "cover",
    backgroundRepeat: "no-repeat",
    backgroundPosition: "center",
    borderRadius: "6px",
    // padding: "2px",
    // backgroundColor: "white"
  };

  const startGame = () => {
    setGameStarted(true);
  };


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



  return (
    <>
      <div style={backgroundStyle} className="font-montserrat">
        {!gameStarted ? (
          <div style={{ display: "flex", flexDirection: "column", justifyContent: "center", alignItems: "center", height: "100%", color: "white", }} >
            <h2>Welcome to Bear Dodge Game</h2>
            {isWalletConnected ? (
              <>
                <button onClick={startGame} className="mt-5 px-5 py-2.5 text-lg bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 cursor-pointer" > Start Game </button>
              </>
            ) : (
              <>
                <p>Please connect your wallet to begin</p>
              </>
            )}
          </div>
        ) : (
          <>
            <BearDodgeGame
              walletAddress={userData?.wallet_address}
            />
          </>
        )}
        {/* <div style={{ position: "absolute", top: "10px", right: "20px", zIndex: 1000, }} >
          <WalletIntegration
            onConnect={(user) => {
              setIsWalletConnected(true);
              setUserData(user);
            }}
          />
        </div> */}
      </div>
    </>
  );
}

export default GamePlay;
