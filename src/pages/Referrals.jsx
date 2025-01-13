import React, { useEffect, useState } from "react";
import { FaUser, FaCopy } from "react-icons/fa";
import { useNavigate } from "react-router-dom";
import { AppProvider, useAppContext } from "../components/context/AppContext";
import { URL } from "../constants";

const ReferralPage = ({ isWalletConnected, userData }) => {
  const [userReferrals, setUserReferrals] = useState([]);
  const [referralsLoading, setReferralsLoading] = useState(false);
  const [referralsError, setReferralsError] = useState(null);
  const navigate = useNavigate();

  const { user } = useAppContext(); // Access context here

  // Fetch Referrals
  const fetchReferrals = async () => {
    setReferralsLoading(true);
    setReferralsError(null);

    try {
      const response = await fetch(
        `http://127.0.0.1:8000/api/wallet-referrals/${user?.wallet_address}`
      );

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
    if (user?.wallet_address) {
      fetchReferrals();
    }
  }, [user?.wallet_address]);

  // useEffect(() => {
  //   if (isWalletConnected && user?.wallet_address) {
  //     fetchReferrals();
  //   }
  // }, [isWalletConnected, user?.wallet_address]);


  const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    alert("Referral link copied to clipboard!");
  };

  const formatDate = (date) => {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    const formattedDate = new Date(date).toLocaleDateString('en-GB', options);
  
    // Get the day to append the suffix
    const day = new Date(date).getDate();
    const suffix = day => {
      if (day > 3 && day < 21) return 'th';
      switch (day % 10) {
        case 1: return 'st';
        case 2: return 'nd';
        case 3: return 'rd';
        default: return 'th';
      }
    };
  
    return formattedDate.replace(/\d+/, (day) => `${day}${suffix(day)}`);
  };
  
  
  

  return (
    <div className="min-h-screen bg-gray-50 text-gray-800 rounded-lg border border-stroke dark:bg-boxdark dark:drop-shadow-none dark:text-white dark:border-strokedark">
      <div className="max-w-6xl mx-auto px-4 py-8">
        {/* Page Header */}
        <header className="mb-8 text-center">
          <h1 className="text-3xl font-bold "> 🎉 Your Referrals 🎉 </h1>
          <p className=" mt-2"> Share your referral link and track your progress! </p>
        </header>

        {/* Referral Link Section */}
        <section className="bg-white dark:bg-boxdark border border-stroke dark:border-strokedark  rounded-lg p-6 mb-8">
          <h2 className="text-lg font-bold mb-4"> Share Your Referral Link </h2>
          <div className="flex flex-col lg:flex-row gap-2 items-center space-x-4">
            <input type="text" value={`${URL}/referral/${user?.wallet_address}`} readOnly
              className="w-4/5 rounded border border-stroke bg-gray py-3 pl-11.5 pr-4.5 text-black focus:border-primary focus-visible:outline-none dark:border-strokedark dark:bg-meta-4 dark:text-white dark:focus:border-primary"
            />
            <button onClick={() => copyToClipboard(`${URL}/referral/${user?.wallet_address}`)} className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition" > <FaCopy className="inline-block mr-1" /> Copy </button>
          </div>
        </section>

        <section className="bg-white dark:bg-boxdark border border-stroke dark:border-strokedark rounded-lg p-6 mb-8 shadow-md">
  <h2 className="text-xl font-bold mb-6 text-center text-gray-800 dark:text-white">Your Referrals</h2>

  {referralsLoading ? (
    <p className="text-gray-600 text-center">Loading referrals...</p>
  ) : referralsError ? (
    <p className="text-red-500 text-center">Error: {referralsError}</p>
  ) : userReferrals.length > 0 ? (
    <ul className="divide-y divide-gray-200 dark:divide-strokedark">
      {userReferrals.map((referral, index) => (
        <li
          key={index}
          className="flex items-center justify-between py-4 px-4 rounded-lg hover:bg-gray-100 dark:hover:bg-meta-4 transition"
        >
          {/* User Icon with Avatar */}
          <div className="flex items-center space-x-4">
            <img
              className="w-12 h-12 rounded-full object-cover"
              src={`https://randomuser.me/api/portraits/lego/${index % 10}.jpg`}
              alt={`Avatar of ${referral.username || 'User'}`}
            />
            <div>
              <p className="font-medium text-gray-900 dark:text-white">
                Username: {referral.username || 'N/A'}
              </p>
              <p className="text-sm text-gray-600 dark:text-gray-400">
    Joined on: {referral.user_joined_date ? formatDate(referral.user_joined_date) : 'N/A'}
  </p>
            </div>
          </div>

          {/* Points */}
          <div className="text-center">
            <p className="font-bold text-blue-600 dark:text-blue-400">
              {referral.userPoints || 0} CB
            </p>
          </div>
        </li>
      ))}
    </ul>
  ) : (
    <div className="text-center">
      <p className="text-gray-600 dark:text-gray-400">You have no referrals yet.</p>
      <p className="text-sm mt-2 text-blue-600 hover:underline cursor-pointer">
        Share your referral link to start earning rewards!
      </p>
    </div>
  )}
</section>

      </div>
    </div>
  );
};

export default ReferralPage;
