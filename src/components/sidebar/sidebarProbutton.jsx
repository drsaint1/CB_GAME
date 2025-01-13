import React, { useState, useEffect } from 'react';
import { Link } from "react-router-dom";
import { FaUser, FaCopy } from "react-icons/fa";

const SidebarProButton = ({user}) => {

  const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    alert("Referral link copied to clipboard!");
  };


  return (
    <div className="bg-gray-800 p-4 rounded-lg text-center shadow-md border border-[#1C2434] mx-5 sticky top-10 text-white">
      <h2 className="text-white text-lg font-semibold"> Wallet : </h2>
      <p className='text-sm whitespace-pre-wrap'> {user?.wallet_address}</p>
      <p className='whitespace-pre-wrap'>Points : {user?.points}</p>
       {/* <p className="text-gray text-sm">  Referral Link:{" "} <a href={`https://bear-dodge-game.com/referral/${user?.wallet_address}`} className="underline hover:text-green-400" > Copy Link </a> </p> */}
       {/* <div className="flex flex-col lg:flex-row gap-2 items-center space-x-4">
              <input type="text" value={`${URL}/referral/${user?.wallet_address}`} readOnly
                className="w-4/5 rounded border border-stroke bg-gray py-3 pl-11.5 pr-4.5 text-black focus:border-primary focus-visible:outline-none dark:border-strokedark dark:bg-meta-4 dark:text-white dark:focus:border-primary"
              />
              <button onClick={() => copyToClipboard( `https://bear-dodge-game.com/referral/${user?.wallet_address}` ) } className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition" > <FaCopy className="inline-block mr-1" /> Copy </button>
            </div> */}
       <Link to="/play" className="mt-4 inline-block bg-green-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 transition duration-300 " > Play Game </Link>
    </div>
  );
};

export default SidebarProButton;
