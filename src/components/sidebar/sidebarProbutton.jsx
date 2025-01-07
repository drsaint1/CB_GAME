import React, { useState, useEffect } from 'react';
import { Link } from "react-router-dom";

const SidebarProButton = () => {

  const [userData, setUserData] = useState(null);
  const [isWalletConnected, setIsWalletConnected] = useState(false);

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

  return (
    <div className="bg-gray-800 p-4 rounded-lg text-center shadow-md border border-[#1C2434] mx-5 sticky top-10">
      <h2 className="text-white text-lg font-semibold"> Wallet : </h2>
      <p className='text-sm whitespace-pre-wrap'> {userData?.wallet_address}</p>
       <p className="text-gray text-sm">  Referral Link:{" "} <a href={`https://bear-dodge-game.com/referral/${userData?.wallet_address}`} className="underline hover:text-green-400" > Copy Link </a> </p>
       <Link to="/upgrade" className="mt-4 inline-block bg-green-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 transition duration-300 " > Play Game </Link>
    </div>
  );
};

export default SidebarProButton;
