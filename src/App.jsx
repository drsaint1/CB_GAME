import React, { useState } from "react";
import WalletIntegration from "./components/WalletIntegration";
import BearDodgeGame from "./scenes/BearDodgeGame";
import './App.css';

function App() {
  const [isWalletConnected, setIsWalletConnected] = useState(false);
  const [gameStarted, setGameStarted] = useState(false);

  const backgroundStyle = {
    position: "relative",
    height: "100vh",
    width: "100vw",
    overflow: "hidden",
    backgroundImage: "url('../assets/background.jpg')", // Adjust path as needed
    backgroundSize: "cover",
    backgroundRepeat: "no-repeat",
    backgroundPosition: "center",
  };

  const startGame = () => {
    setGameStarted(true);
  };

  return (
    <div style={backgroundStyle} className="font-montserrat">
      {!gameStarted ? (
        <div style={{ display: "flex", flexDirection: "column", justifyContent: "center", alignItems: "center", height: "100%", color: "white", }} >
          <h2>Welcome to Bear Dodge Game</h2>
          {isWalletConnected ? (
            <button onClick={startGame} className="px-5 py-2.5 text-lg bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 cursor-pointer" > Start Game </button>
          ) : (
            <p>Please connect your wallet to begin</p>
          )}
        </div>
      ) : (
        <BearDodgeGame />
      )}
      <div style={{ position: "absolute", top: "10px", right: "20px", zIndex: 1000, }} >
        <WalletIntegration onConnect={() => setIsWalletConnected(true)} />
      </div>
    </div>
  );
}

export default App;
