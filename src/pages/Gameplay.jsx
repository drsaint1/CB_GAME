import React, { useState, useEffect, useMemo } from "react";
import WalletIntegration from "../components/WalletIntegration";
import BearDodgeGame from "../scenes/BearDodgeGame";
import "../App.css";
import { Link } from "react-router-dom";
import OldBearDodgeGame from "../scenes/OldBearDodgeGame";
import {
  initializeProgram,
  useInitializeConfig,
  useInitializeSession,
} from "../hooks/useContracts";
import { useWallet } from "@solana/wallet-adapter-react";
import { toast } from "react-toastify";
// import FaUser

function GamePlay() {
  const [isWalletConnected, setIsWalletConnected] = useState(false);
  const [gameStarted, setGameStarted] = useState(false);
  const [sessionPDA, setSessionPDA] = useState(null);
  const [configPDA, setConfigPDA] = useState(null);
  const [userData, setUserData] = useState(null);
  const [gameConfig, setGameconfig] = useState(false);
  const [programState, setProgramState] = useState(null);

  const { wallet } = useWallet();

  const program = useMemo(() => {
    if (wallet) {
      return initializeProgram(wallet);
    }
    return null;
  }, [wallet]);

  const { initializeSession } = useInitializeSession(program);
  const { initializeConfig } = useInitializeConfig(program);

  useEffect(() => {
    if (program) {
      setProgramState(program);
    }
  }, [program]);

  useEffect(() => {
    if (!sessionPDA && gameConfig) {
      const initialize = async () => {
        try {
          const pda = await initializeSession();
          if (pda) {
            setSessionPDA(pda);
            gameConfig(true);
            toast.dismiss();
            toast.success("✅ Session initialized successfully!");
          } else {
            throw new Error("🚨 Session PDA is undefined!");
          }
        } catch (err) {
          toast.dismiss();
          toast.error("❌ Error initializing session");
        }
      };

      initialize();
    }
  }, [wallet, sessionPDA, gameConfig]);

  useEffect(() => {
    if (sessionPDA && !configPDA && gameConfig) {
      const initialize = async () => {
        try {
          const config = await initializeConfig(100, 100, 100);
          if (config) {
            setConfigPDA(config);
            setGameStarted(true);
            toast.dismiss();
            toast.success("✅ Configuration loaded!");
          } else {
            throw new Error("🚨 Config PDA is undefined!");
          }
        } catch (err) {
          toast.dismiss();
          toast.error("❌ Error initializing config");
        }
      };

      initialize();
    }
  }, [sessionPDA, configPDA, gameConfig]);

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
    // setGameStarted(true);
    setGameconfig(true);
  };

  return (
    <>
      {((!sessionPDA || !configPDA) && gameConfig) && (
        <div className="loading-screen">
          <div className="loading-content">
            <div className="spinner"></div>
            <p className="loading-text">
              Please wait while we configure the game for you...
            </p>
          </div>
        </div>
      )}
      <div style={backgroundStyle} className="font-montserrat">
        {!gameStarted ? (
          <div
            style={{
              display: "flex",
              flexDirection: "column",
              justifyContent: "center",
              alignItems: "center",
              height: "100%",
              color: "white",
            }}
          >
            <h2>Welcome to Bear Dodge Game</h2>
            {isWalletConnected ? (
              <>
                <button
                  onClick={startGame}
                  className="mt-5 px-5 py-2.5 text-lg bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 cursor-pointer"
                >
                  {" "}
                  Start Game{" "}
                </button>
              </>
            ) : (
              <>
                <p>Please connect your wallet to begin</p>
              </>
            )}
          </div>
        ) : (
          <>
            <BearDodgeGame sessionPDA={sessionPDA} configPDA={configPDA} />
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
