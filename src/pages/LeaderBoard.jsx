import React, { useEffect, useState } from "react";
import { AppProvider, useAppContext } from "../components/context/AppContext";
import { URL } from "../constants";

const LeaderboardPage = () => {
  const [leaderboard, setLeaderboard] = useState([]);
  const [leaderboardLoading, setLeaderboardLoading] = useState(false);
  const [leaderboardError, setLeaderboardError] = useState(null);
  const [currentUserRank, setCurrentUserRank] = useState(null); // Add a state for the current user rank

  const { user } = useAppContext(); 

  // Fetch leaderboard data
  const fetchLeaderboard = async () => {
    setLeaderboardLoading(true);
    setLeaderboardError(null);

    try {
      const response = await fetch(`http://127.0.0.1:8000/api/leaderboard/${user?.wallet_address}`);

      if (!response.ok) {
        throw new Error(`Failed to fetch leaderboard: ${response.statusText}`);
      }

      const data = await response.json();
      console.log('data',data);
      setLeaderboard(data.leaderboard || []);
      setCurrentUserRank(data.currentUserRank || null); // Assume backend sends current user rank
    } catch (error) {
      console.error("Error fetching leaderboard:", error.message);
      setLeaderboardError(error.message);
    } finally {
      setLeaderboardLoading(false);
    }
  };

  // useEffect(() => {
  //   fetchLeaderboard();
  // }, []);

  useEffect(() => {
    if (user?.wallet_address) {
      fetchLeaderboard();
    }
  }, [user?.wallet_address]);

  return (
    <div className="min-h-screen bg-gray-50 text-gray-800 dark:bg-boxdark dark:drop-shadow-none dark:text-white border dark:border-boxdark rounded-lg ">
      <div className="max-w-6xl mx-auto px-4 py-8">
        {/* Page Header */}
        <header className="mb-8">
          <h1 className="text-3xl font-bold text-gray-800 dark:text-white text-center"> 🏆 Leaderboard 🏆 </h1>
          {currentUserRank && (
            <div className="mt-4 bg-blue-100 border border-blue-300 rounded-lg p-4 text-center shadow-sm dark:bg-black dark:border-strokedark" >
              <p className="text-lg"> Your Current Rank:{" "} <span className="text-blue-600 font-semibold"> {currentUserRank.rank} / {currentUserRank.totalUsers} </span> </p>
              <p > Points: {currentUserRank.points} CB </p>
            </div>
          )}
        </header>

        {/* Leaderboard Section */}
        <div className="bg-white rounded-lg border border-stroke dark:border-strokedark p-6  dark:bg-boxdark dark:drop-shadow-none dark:text-white">
          {leaderboardLoading ? (
            <p className="text-center text-lg text-gray-500"> Loading leaderboard... </p>
          ) : leaderboardError ? (
            <p className="text-center text-lg text-red-500"> Error: {leaderboardError} </p>
          ) : leaderboard.length > 0 ? (
            <ul className="divide-y divide-gray-200">
              {leaderboard.map((player, index) => (
                <li key={index} className="flex items-center justify-between py-4 px-4 hover:bg-gray-50 dark:hover:bg-black dark:text-white" >
                  <div className="flex items-center">
                    <span className="text-lg font-medium mr-4"> #{index + 1} </span>
                    <img className="w-12 h-12 rounded-full object-cover mr-4" src={ player.avatar || `https://randomuser.me/api/portraits/lego/${ index % 10 }.jpg` } alt={`Avatar of ${player.username}`} />
                    <div>
                      <h3 className="text-lg font-semibold "> {player.username} </h3>
                      <p className=""> {player.points} CB </p>
                    </div>
                  </div>
                  <span className={`text-sm px-3 py-1 rounded-full ${ index === 0 ? "bg-yellow-100 text-yellow-800" : index === 1 ? "bg-gray-200 text-gray-800" : index === 2 ? "bg-orange-100 text-orange-800" : "bg-gray-100 text-gray-600" }`} >
                    {index === 0 ? "🥇 Top Player" : index === 1 ? "🥈 Second" : index === 2 ? "🥉 Third" : "Player"}
                  </span>
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-center text-lg text-gray-500">
              No leaderboard data available.
            </p>
          )}
        </div>
      </div>
    </div>
  );
};

export default LeaderboardPage;
