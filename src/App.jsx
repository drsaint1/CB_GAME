import React, { useState, useEffect } from "react";
import WalletIntegration from "./components/WalletIntegration";
import BearDodgeGame from "./scenes/BearDodgeGame";
import './App.css';
import { Link } from "react-router-dom";


function Header({ isWalletConnected, onConnect, userData, onGameStart }) {
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);

  const toggleSidebar = () => {
    setIsSidebarOpen((prev) => !prev);
  };

  return (
    <header className="bg-gradient-to-r from-blue-900 to-blue-700 text-white shadow-md font-montserrat">
      {/* Top Bar */}
      <div className=" mx-auto flex items-center justify-between px-4 py-4 ">
        {/* Logo */}
        <div className="text-2xl font-bold cursor-pointer tracking-wide">
          Bear Dodge Game
        </div>

        {/* Navigation for Larger Screens */}
        <div className="hidden xs:flex md:items-center space-x-6">
          <a href="#" className="hover:text-rose-500 transition duration-200">
            Dashboard
          </a>
          <a href="#" className="hover:text-rose-500 transition duration-200">
            Referral
          </a>
          <a href="#" className="hover:text-rose-500 transition duration-200">
            Leaderboard
          </a>
       
        </div>

        {/* Wallet and Game Controls */}
        <div className="flex items-center space-x-4">
          {isWalletConnected ? (
            <div className="hidden md:flex flex-col items-end space-y-1">
              <span className="text-sm font-medium"> Wallet: {userData?.wallet_address} </span>
              <span className="text-sm font-medium"> Referral Link:{" "} <a href={`https://bear-dodge-game.com/referral/${userData?.wallet_address}`} className="underline hover:text-green-400" > Copy Link </a> </span>
              <button onClick={onGameStart} className="px-4 py-2 bg-green-600 rounded hover:bg-green-700 focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200" > Start Game </button>
            </div>
          ) : (
            <WalletIntegration onConnect={onConnect} />
          )}

      
          <button onClick={toggleSidebar} className="md:hidden p-2 text-white bg-blue-600 rounded-lg hover:bg-blue-500 focus:ring-2 focus:ring-blue-400" > ☰ </button>
        </div>
        
      </div>

      {/* Sidebar for Mobile */}
      {isSidebarOpen && (
        <div className="fixed top-0 left-0 h-screen w-2/3 bg-blue-800 text-white shadow-lg z-50 md:hidden">
          <div className="flex flex-col h-full">
            {/* Close Button */}
            
            <button onClick={toggleSidebar} className="self-end m-4 p-2 text-lg bg-blue-700 rounded hover:bg-blue-600" > ✕ </button>

            {/* Navigation Links */}
            <nav className="flex flex-col items-start space-y-4 px-6">
              <a href="#" className="hover:underline text-lg">
                Home
              </a>
              <a href="#" className="hover:underline text-lg">
                About
              </a>
              <a href="#" className="hover:underline text-lg">
                Leaderboard
              </a>
            
            </nav>

            {/* Wallet and Game Controls */}
            <div className="mt-auto px-6 pb-6 space-y-2">
              {isWalletConnected ? (
                <>
                  <div className="text-sm">
                    Wallet: {userData?.wallet_address}
                  </div>
                  <div className="text-sm"> Referral Link:{" "} <a href={`https://bear-dodge-game.com/referral/${userData?.wallet_address}`} className="underline hover:text-green-400" > Copy Link </a> </div>
                  <button onClick={onGameStart} className="w-full px-4 py-2 bg-green-600 rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-offset-2 focus:ring-green-500" > Start Game </button>
                </>
              ) : (
                <WalletIntegration onConnect={onConnect} />
              )}
            </div>
          </div>
        </div>
      )}
    </header>
  );
}

// export default Header;


function App() {
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
    <>

      <Header
        isWalletConnected={isWalletConnected}
        onConnect={(user) => {
          setIsWalletConnected(true);
          setUserData(user);
        }}
        userData={userData}
        onGameStart={startGame}
      />


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
          <BearDodgeGame
            walletAddress={userData?.wallet_address}
          />
        )}
        <div style={{ position: "absolute", top: "10px", right: "20px", zIndex: 1000, }} >

          {/* <WalletIntegration onConnect={() => setIsWalletConnected(true)} /> */}
          <WalletIntegration
            onConnect={(user) => {
              setIsWalletConnected(true);
              setUserData(user);
            }}
          />
        </div>
      </div>
    </>
  );
}

export default App;
