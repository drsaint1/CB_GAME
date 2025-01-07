import React, { useState, useEffect } from 'react';
import { Link } from "react-router-dom";
import DarkModeSwitcher from "./DarkModeSwitcher";
// import DropdownMessage from "./DropdownMessage";
// import DropdownNotification from "./DropdownNotification";
import DropdownUser from "./DropdownUser";
import { FaCopy, FaCrown, FaUser } from 'react-icons/fa';
// import DropdownRequest from "./DropdownRequest";
// import { useAppContext } from "../context/AppContext";

const Header = ({ sidebarOpen, setSidebarOpen }) => {

  // const {  unreadNotificationCount, setUnreadNotificationCount, unreadRequestCount, setUnreadRequestCount, unreadInboxCount, setUnreadInboxCount, user, setUserData } = useAppContext();

  const [isReferralOpen, setIsReferralOpen] = useState(false);
  const [isLeaderboardOpen, setIsLeaderboardOpen] = useState(false);
  const [userReferrals, setUserReferrals] = useState([]);
  const [leaderboard, setLeaderboard] = useState([]);
  const [referralsLoading, setReferralsLoading] = useState(false);
  const [referralsError, setReferralsError] = useState(null);

  const [leaderboardLoading, setLeaderboardLoading] = useState(false);
  const [leaderboardError, setLeaderboardError] = useState(null);

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
    <header className="sticky top-0 z-50 flex w-full bg-white drop-shadow-1 dark:bg-boxdark dark:drop-shadow-none">
      <div className="flex flex-grow items-center justify-between px-4 py-4 shadow-2 md:px-6 2xl:px-11">
        <div className="flex items-center gap-2 sm:gap-4 lg:hidden">
          {/* Hamburger Toggle BTN */}
          <button aria-controls="sidebar" onClick={(e) => { e.stopPropagation(); setSidebarOpen(!sidebarOpen); console.log("Header - Toggled Sidebar"); }} className="z-50 block rounded-sm border border-stroke bg-white p-1.5 shadow-sm dark:border-strokedark dark:bg-boxdark lg:hidden" >
            <span className="relative block h-5.5 w-5.5 cursor-pointer">
              <span className="du-block absolute right-0 h-full w-full">
                <span
                  className={`relative left-0 top-0 my-1 block h-0.5 w-0 rounded-sm bg-black delay-[0] duration-200 ease-in-out dark:bg-white ${!sidebarOpen && "!w-full delay-300"
                    }`}
                ></span>
                <span
                  className={`relative left-0 top-0 my-1 block h-0.5 w-0 rounded-sm bg-black delay-150 duration-200 ease-in-out dark:bg-white ${!sidebarOpen && "delay-400 !w-full"
                    }`}
                ></span>
                <span
                  className={`relative left-0 top-0 my-1 block h-0.5 w-0 rounded-sm bg-black delay-200 duration-200 ease-in-out dark:bg-white ${!sidebarOpen && "!w-full delay-500"
                    }`}
                ></span>
              </span>
              <span className="absolute right-0 h-full w-full rotate-45">
                <span
                  className={`absolute left-2.5 top-0 block h-full w-0.5 rounded-sm bg-black delay-300 duration-200 ease-in-out dark:bg-white ${!sidebarOpen && "!h-0 !delay-[0]"
                    }`}
                ></span>
                <span
                  className={`delay-400 absolute left-0 top-2.5 block h-0.5 w-full rounded-sm bg-black duration-200 ease-in-out dark:bg-white ${!sidebarOpen && "!h-0 !delay-200"
                    }`}
                ></span>
              </span>
            </span>
            {/* ☰ */}

          </button>
          {/* Hamburger Toggle BTN */}

          <Link className="block flex-shrink-0 lg:hidden" to="/">
            {/* <img
              width={32}
              height={32}
              src="/images/logo/blackbucxai.jpg"
              alt="Logo"
            /> */}
            <span className="text-black font-bold text-3xl dark:text-white">CB GAME
              {/* {unreadNotificationCount} */}
            </span>
          </Link>
        </div>

        {/*  */}

        <div className="flex items-center gap-3 2xsm:gap-7 w-full ">
          <ul className="flex items-center gap-2 2xsm:gap-4  w-full justify-between">
            {/* Dark Mode Toggler */}

            {/* Dark Mode Toggler */}

            {/* Notification Menu Area */}
            {/* <DropdownNotification /> */}
            {/* Notification Menu Area */}

            {/* Chat Notification Area */}
            {/* <DropdownMessage /> */}
            {/* Chat Notification Area */}

            {/* <DropdownRequest/> */}


            {/* User Area */}
            {/* <DropdownUser /> */}
            {/* User Area */}

            <div className=" flex md:items-center space-x-6">
              {/* Referral Dropdown */}
              <div className="relative">
                <button onClick={() => setIsReferralOpen(!isReferralOpen)} className="flex items-center font-medium inline-block bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 transition duration-300 border border-blue-600" > Referral <FaUser className="ml-2" /> {/* <FaUser className="ml-2" /> */} </button>
                {isReferralOpen && (
                  <div className="absolute top-full mt-2 bg-white text-black rounded shadow-lg w-100 p-4 z-50">
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
                        <button onClick={() => copyToClipboard(`https://bear-dodge-game.com/referral/${userData?.wallet_address}`)} className="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700" > <FaCopy/> {/* <FaCopy /> */} </button>
                      </div>
                    </div>
                  </div>
                )}
              </div> 

              {/* Leaderboard Dropdown */}
              <div className="relative">
                <button onClick={() => setIsLeaderboardOpen(!isLeaderboardOpen)} className=" flex items-center font-medium inline-block bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 transition duration-300 border border-blue-600" > Leaderboard <FaCrown  className="ml-2"/>  </button>
                {isLeaderboardOpen && (

                  <div className="absolute top-full mt-2 bg-white text-black rounded shadow-lg w-100 p-4 z-50 max-w-lg">
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



            <DarkModeSwitcher />


          </ul>



        </div>
      </div>
    </header>
  );
};

export default Header;
