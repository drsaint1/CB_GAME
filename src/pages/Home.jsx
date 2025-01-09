import React, { useState, useEffect } from 'react';

import { Link } from "react-router-dom";
import WalletIntegration from "../components/WalletIntegration";

function Home() {
    const [isWalletConnected, setIsWalletConnected] = useState(false);
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
            localStorage.removeItem("walletData");
          }
        }
      }, []);


    return (
        <div className="min-h-screen bg-gradient-to-b from-gray-900 to-black text-white flex flex-col justify-center items-center font-montserrat">
            <h1 className="text-5xl font-bold mb-4"> Welcome to Bear Dodge Game! </h1>
            <p className="text-gray-300 text-center mb-8 max-w-2xl">
                Register your wallet to join the game, earn rewards, and compete with others for the top leaderboard position. Don't forget to share your referral link to invite friends and earn bonus points!
            </p>
            <div className="flex space-x-4">
                    <WalletIntegration
                        onConnect={(user) => {
                            setIsWalletConnected(true);
                            setUserData(user);
                        }}
                    />
                <Link to="/leaderboard" className="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:outline-none transition" > View Leaderboard </Link>
            </div>
        </div>
    );
}

export default Home;
