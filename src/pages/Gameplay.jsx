import React, { useState, useEffect } from "react";
import WalletIntegration from "../components/WalletIntegration";
import BearDodgeGame from "../scenes/BearDodgeGame";
import '../App.css';
import { Link } from "react-router-dom";
// import FaUser


function Header({ isWalletConnected, onConnect, userData, onGameStart }) {
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);
  const [isReferralOpen, setIsReferralOpen] = useState(false);
  const [isLeaderboardOpen, setIsLeaderboardOpen] = useState(false);
  const [userReferrals, setUserReferrals] = useState([]);
  const [leaderboard, setLeaderboard] = useState([]);
  const [referralsLoading, setReferralsLoading] = useState(false);
  const [referralsError, setReferralsError] = useState(null);

  const [leaderboardLoading, setLeaderboardLoading] = useState(false);
  const [leaderboardError, setLeaderboardError] = useState(null);


  const toggleSidebar = () => {
    setIsSidebarOpen((prev) => !prev);
  };

  useEffect(() => {
    if (!isWalletConnected) return;

    // Fetch User Referrals
    const fetchReferrals = async () => {
      setReferralsLoading(true);
      setReferralsError(null);

      try {
        const response = await fetch("http://127.0.0.1:8000/api/referrals", {
          headers: {
            Authorization: `Bearer ${userData?.token}`,
          },
        });

        if (!response.ok) {
          throw new Error(`Failed to fetch referrals: ${response.statusText}`);
        }

        const data = await response.json();
        setUserReferrals(data);
      } catch (error) {
        console.error("Error fetching referrals:", error.message);
        setReferralsError(error.message);
      } finally {
        setReferralsLoading(false);
      }
    };

    // Fetch Leaderboard
    const fetchLeaderboard = async () => {
      setLeaderboardLoading(true);
      setLeaderboardError(null);

      try {
        const response = await fetch("http://127.0.0.1:8000/api/leaderboard");

        if (!response.ok) {
          throw new Error(`Failed to fetch leaderboard: ${response.statusText}`);
        }

        const data = await response.json();
        console.log("Leaderboard data:", data);
        setLeaderboard(data);
      } catch (error) {
        console.error("Error fetching leaderboard:", error.message);
        setLeaderboardError(error.message);
      } finally {
        setLeaderboardLoading(false);
      }
    };

    fetchReferrals();
    fetchLeaderboard();
  }, [isWalletConnected]);

  // [isWalletConnected, userData]



  const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    alert("Referral link copied to clipboard!");
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
        <div className=" md:flex md:items-center space-x-6">
          {/* Referral Dropdown */}
          <div className="relative">
            <button onClick={() => setIsReferralOpen(!isReferralOpen)} className="hover:text-rose-500 transition duration-200 flex items-center" > Referral {/* <FaUser className="ml-2" /> */} </button>
            {isReferralOpen && (
              <div className="absolute top-full mt-2 bg-white text-black rounded shadow-lg w-64 p-4 z-50">
                <h4 className="text-lg font-bold mb-2">Your Referrals</h4>
                {referralsLoading && <p>Loading referrals...</p>}
                {referralsError && <p style={{ color: "red" }}>Error: {referralsError}</p>}

                {userReferrals.length > 0 ? (
                  <ul className="space-y-2">
                    {userReferrals.map((referral, index) => (
                      <li key={index} className="flex items-center space-x-2">
                        <FaUser className="text-blue-500" />
                        <span>{referral.name}</span>
                      </li>


                    ))}
                  </ul>
                ) : (
                  <p>No referrals yet.</p>
                )}
                <div className="mt-4">
                  <p className="text-sm mb-2">Your Referral Link:</p>
                  <div className="flex items-center space-x-2">
                    <input type="text" value={`https://bear-dodge-game.com/referral/${userData?.wallet_address}`} readOnly className="flex-1 border px-2 py-1 rounded text-sm" />
                    <button onClick={() => copyToClipboard(`https://bear-dodge-game.com/referral/${userData?.wallet_address}`)} className="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700" > {/* <FaCopy /> */} </button>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Leaderboard Dropdown */}
          <div className="relative">
            <button onClick={() => setIsLeaderboardOpen(!isLeaderboardOpen)} className="hover:text-rose-500 transition duration-200 flex items-center" > Leaderboard {/* <FaCrown className="ml-2" /> */} </button>
            {isLeaderboardOpen && (

              <div className="absolute top-full mt-2 bg-white text-black rounded shadow-lg w-64 p-4 z-50 max-w-lg">
                <div className="bg-gray-100 py-2 px-4">
                  <h2 className="text-xl font-semibold text-gray-800">Top Users</h2>
                </div>
                {leaderboardLoading ? (
                  <div className="py-4 px-6">
                    <p>Loading leaderboard...</p>
                  </div>
                ) : leaderboardError ? (
                  <div className="py-4 px-6">
                    <p style={{ color: "red" }}>Error: {leaderboardError}</p>
                  </div>
                ) : leaderboard.length > 0 ? (
                  <ul className="divide-y divide-gray-200">
                    {leaderboard.map((player, index) => (
                      <li key={index} className="flex items-center py-4 px-6">
                        <span className="text-gray-700 text-lg font-medium mr-4">{index + 1}.</span>
                        <img
                          className="w-12 h-12 rounded-full object-cover mr-4"
                          src={player.avatar || `https://randomuser.me/api/portraits/lego/${index % 10}.jpg`}
                          alt={`Avatar of ${player.username}`}
                        />
                        <div className="flex-1">
                          <h3 className="text-lg font-medium text-gray-800">{player.username}</h3>
                          <p className="text-gray-600 text-base">{player.points} points</p>
                        </div>
                      </li>
                    ))}
                  </ul>
                ) : (
                  <div className="py-4 px-6">
                    <p>No leaderboard data available.</p>
                  </div>
                )}
              </div>

            )}
          </div>

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
  };

  const startGame = () => {
    setGameStarted(true);
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
          <BearDodgeGame
            walletAddress={userData?.wallet_address}
          />
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
