import React, { useState } from "react";

const WalletPage = ({ isWalletConnected = false, userData = {} }) => {
  const [transactions, setTransactions] = useState([
    // Example transactions; replace with API data
    { date: "2025-01-10", description: "Deposit", amount: 100, status: "Success" },
    { date: "2025-01-08", description: "Withdrawal", amount: 50, status: "Pending" },
  ]);

  return (
    <section className="container mx-auto p-6">
      {/* Wallet Header */}
      <div className="bg-blue-100 dark:bg-blue-900 border border-blue-300 dark:border-blue-700 rounded-lg p-6 shadow-md">
        <h1 className="text-2xl font-bold text-gray-800 dark:text-gray-200">My Wallet</h1>
        <p className="text-sm text-gray-600 dark:text-gray-400 mt-2">
          Manage your wallet balance, view transactions, and withdraw funds.
        </p>
      </div>

      {/* Wallet Summary */}
      <div className="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Current Balance */}
        <div className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 shadow-md">
          <h2 className="text-lg font-bold text-gray-800 dark:text-gray-200">Current Balance</h2>
          <p className="text-3xl font-semibold text-blue-600 dark:text-blue-400 mt-4">
            ${userData?.balance || "0.00"}
          </p>
          <button className="mt-4 bg-blue-600 text-white dark:bg-blue-500 px-4 py-2 rounded hover:bg-blue-700 dark:hover:bg-blue-600">
            Deposit Funds
          </button>
        </div>

        {/* Withdraw Funds */}
        <div className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 shadow-md">
          <h2 className="text-lg font-bold text-gray-800 dark:text-gray-200">Withdraw Funds</h2>
          <p className="text-sm text-gray-600 dark:text-gray-400 mt-2">
            You can withdraw your funds to your linked bank account.
          </p>
          <button className="mt-4 bg-green-600 text-white dark:bg-green-500 px-4 py-2 rounded hover:bg-green-700 dark:hover:bg-green-600">
            Withdraw
          </button>
        </div>
      </div>

      {/* Transaction History */}
      <div className="mt-8 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 shadow-md">
        <h2 className="text-lg font-bold text-gray-800 dark:text-gray-200">Transaction History</h2>
        <table className="w-full mt-4 border-collapse">
          <thead>
            <tr className="text-left border-b border-gray-200 dark:border-gray-700">
              <th className="py-2 px-4 text-gray-600 dark:text-gray-400">Date</th>
              <th className="py-2 px-4 text-gray-600 dark:text-gray-400">Description</th>
              <th className="py-2 px-4 text-gray-600 dark:text-gray-400">Amount</th>
              <th className="py-2 px-4 text-gray-600 dark:text-gray-400">Status</th>
            </tr>
          </thead>
          <tbody>
            {transactions?.length > 0 ? (
              transactions.map((txn, index) => (
                <tr key={index} className="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700" >
                  <td className="py-2 px-4 text-gray-800 dark:text-gray-300">{txn.date}</td>
                  <td className="py-2 px-4 text-gray-800 dark:text-gray-300">{txn.description}</td>
                  <td className="py-2 px-4 text-gray-800 dark:text-gray-300">${txn.amount}</td>
                  <td className={`py-2 px-4 ${ txn.status === "Success" ? "text-green-600 dark:text-green-400" : "text-red-600 dark:text-red-400" }`} > {txn.status} </td>
                </tr>
              ))
            ) : (
              <tr>
                <td colSpan="4" className="text-center py-4 text-gray-600 dark:text-gray-400" > No transactions yet. </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </section>
  );
};

export default WalletPage;
