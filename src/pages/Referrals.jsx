import React, { useEffect, useState } from "react";
import { FaUser, FaCopy } from "react-icons/fa";

const ReferralPage = ({ isWalletConnected, userData }) => {
  const [userReferrals, setUserReferrals] = useState([]);
  const [referralsLoading, setReferralsLoading] = useState(false);
  const [referralsError, setReferralsError] = useState(null);

  // Fetch Referrals
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

  useEffect(() => {
    if (isWalletConnected) {
      fetchReferrals();
    }
  }, [isWalletConnected]);

  const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    alert("Referral link copied to clipboard!");
  };

  return (
    <div className="min-h-screen bg-gray-50 text-gray-800">
      <div className="max-w-6xl mx-auto px-4 py-8">
        {/* Page Header */}
        <header className="mb-8 text-center">
          <h1 className="text-3xl font-bold text-gray-800">
            🎉 Your Referrals 🎉
          </h1>
          <p className="text-gray-600 mt-2">
            Share your referral link and track your progress!
          </p>
        </header>

        {/* Referral Link Section */}
        <section className="bg-white shadow-md rounded-lg p-6 mb-8">
          <h2 className="text-lg font-bold text-gray-800 mb-4">
            Share Your Referral Link
          </h2>
          <div className="flex items-center space-x-4">
            <input
              type="text"
              value={`https://bear-dodge-game.com/referral/${userData?.wallet_address}`}
              readOnly
              className="flex-1 border border-gray-300 rounded px-4 py-2 text-sm"
            />
            <button
              onClick={() =>
                copyToClipboard(
                  `https://bear-dodge-game.com/referral/${userData?.wallet_address}`
                )
              }
              className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
            >
              <FaCopy className="inline-block mr-1" />
              Copy
            </button>
          </div>
        </section>

        {/* User Referrals Section */}
        <section className="bg-white shadow-md rounded-lg p-6">
          <h2 className="text-lg font-bold text-gray-800 mb-4">
            Your Referrals
          </h2>
          {referralsLoading ? (
            <p className="text-gray-600">Loading referrals...</p>
          ) : referralsError ? (
            <p className="text-red-500">Error: {referralsError}</p>
          ) : userReferrals.length > 0 ? (
            <ul className="divide-y divide-gray-200">
              {userReferrals.map((referral, index) => (
                <li
                  key={index}
                  className="flex items-center py-4 space-x-4 hover:bg-gray-50"
                >
                  <FaUser className="text-blue-500" />
                  <div>
                    <p className="font-medium text-gray-800">{referral.name}</p>
                    <p className="text-sm text-gray-600">
                      Joined on: {referral.date_joined}
                    </p>
                  </div>
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-gray-600">You have no referrals yet.</p>
          )}
        </section>

        {/* User Ranking Section */}
        <section className="mt-8">
          <div className="bg-blue-100 border border-blue-300 rounded-lg p-6 text-center shadow-md">
            <h2 className="text-lg font-bold text-gray-800">
              Your Current Rank
            </h2>
            <p className="text-gray-600 text-sm mt-2">
              Keep sharing your referral link to climb the leaderboard!
            </p>
            <div className="mt-4 bg-white p-4 rounded shadow-lg">
              <p className="text-lg font-semibold text-blue-600">
                Rank: #{userData?.rank || "N/A"}
              </p>
              <p className="text-gray-600 text-sm">
                Total Referrals: {userData?.total_referrals || 0}
              </p>
            </div>
          </div>
        </section>
      </div>
    </div>
  );
};

export default ReferralPage;
