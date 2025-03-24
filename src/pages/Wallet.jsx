import React, { useRef, useEffect, useState, useMemo } from "react";
import "../style/games.css";
import {
  useInitializeSession,
  useWithdrawTokens,
  useSavePoints,
  useContinueGame,
  initializeProgram,
  useWalletData,
  useInitializeConfig,
  useCheckBalance,
} from "../hooks/useContracts";
import { PublicKey } from "@solana/web3.js";
import { useWallet } from "@solana/wallet-adapter-react";
import { useAppContext } from "../components/context/AppContext";
import axios from "axios";
import { toast } from "react-toastify";

function WalletPage() {
  const [amount, setAmount] = useState("");
  const [loading, setLoading] = useState(false);
  const [sessionPDA, setSessionPDA] = useState(null);
  const [configPDA, setConfigPDA] = useState(null);
  const [processingWithdrawal, setProcessingWithdrawal] = useState(false);
  const [transactions, setTransactions] = useState([]);

  const { user } = useAppContext();
  const walletData = JSON.parse(localStorage.getItem("walletData"));
  if (!walletData) return <div>No wallet data found!</div>;
  const { wallet } = useWallet();
  const [programState, setProgramState] = useState(null);

  const program = useMemo(() => {
    if (wallet) {
      return initializeProgram(wallet);
    }
    return null;
  }, [wallet]);

  useEffect(() => {
    if (program) {
      setProgramState(program);
    }
  }, []);
  const fetchTransactions = async () => {
    try {
      // Send wallet address as a query parameter
      const response = await fetch(
        `${
          import.meta.env.VITE_API_URL
        }/transactions?wallet_address=${encodeURIComponent(
          walletData.walletAddress
        )}`,
        {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
          },
        }
      );

      const data = await response.json();

      if (!response.ok) {
        toast.error(data.message || "Failed to fetch transactions");
      }

      setTransactions(data.transactions);
    } catch (error) {
      // console.error(error);
      toast.error("Failed to fetch transactions. Please try again.");
    }
  };

  // Fetch transactions when the component loads
  useEffect(() => {
    if (walletData.walletAddress) {
      fetchTransactions();
    }
  }, [walletData.walletAddress]);

  const { initializeSession } = useInitializeSession(program);
  const { initializeConfig } = useInitializeConfig(program);
  const { withdrawTokens } = useWithdrawTokens(program);
  const { savePoints } = useSavePoints(program);
  const { checkBalance } = useCheckBalance(program);
  useEffect(() => {
    if (programState) {
      initializeSession()
        .then((pda) => {
          initializeConfig(100, 100, 100).then((config) => {
            setSessionPDA(pda);
            checkBalance(pda);
            setConfigPDA(config);
          });
        })
        .catch((err) => {
          console.error("Error initializing session:", err);
          toast.error("Error initializing session.");
        });
    }
  }, [programState]);

  const handleWithdraw = async () => {
    try {
      setLoading(true);
      setProcessingWithdrawal(true); // Show modal

      if (!program) throw new Error("Program not initialized!");

      // Save Points First
      await savePoints(sessionPDA, Math.floor(user.points));
      checkBalance(sessionPDA);

      // Process Withdrawal on Blockchain
      const txId = await withdrawTokens(sessionPDA, amount);
      console.log("Withdrawal Successful on Blockchain, Tx ID:", txId);

      const response = await fetch(`${import.meta.env.VITE_API_URL}/withdraw`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          wallet_address: walletData.walletAddress,
          points: amount,
        }),
      });

      const data = await response.json();
      if (!response.ok) toast.error(data.message || "Failed to update backend");

      toast.success(
        `✅ Withdrawal Successful! \n Blockchain Tx ID: ${txId} \n Transaction ID: ${data.transaction_id}`
      );

      // fetchWalletData(); // Refresh wallet balance
    } catch (error) {
      // console.error(error);
      toast.error(error.message);
    } finally {
      setLoading(false);
      setProcessingWithdrawal(false); // Hide modal
      setAmount("");
    }
  };

  return (
    <section className="container mx-auto p-6">
      <div className="bg-blue-100 dark:bg-blue-900 border border-blue-300 dark:border-blue-700 rounded-lg p-6 shadow-md">
        <h1 className="text-2xl font-bold">My Wallet</h1>
        <p>Manage your wallet balance and transactions.</p>
      </div>

      {/* Display Error Messages */}
      {/* {errorMessage && (
        <div className="bg-red-100 text-red-800 p-4 mt-4 rounded-md">
          ❌ {errorMessage}
        </div>
      )} */}

      {/* Display Success Messages */}
      {/* {successMessage && (
        <div className="bg-green-100 text-green-800 p-4 mt-4 rounded-md">
          {successMessage}
        </div>
      )} */}

      <div className="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="bg-white p-6 rounded-lg shadow-md">
          <h2 className="text-lg font-bold">Current Balance</h2>
          <p className="text-3xl text-blue-600 mt-4">
            {user.points || "0.00"} CB
          </p>
        </div>
        <div className="bg-white p-6 rounded-lg shadow-md">
          <h2 className="text-lg font-bold">Withdraw Tokens</h2>
          <input
            type="number"
            value={amount}
            onChange={(e) => {
              if (e.target.value > user.points) {
                toast.dismiss();
                toast.error(
                  "Withwrawal amount can not be greater than point balance."
                );
              } else {
                setAmount(e.target.value);
              }
            }}
            placeholder="Enter amount"
            className="border p-2 w-full mt-4"
          />
          <button
            onClick={handleWithdraw}
            className="mt-4 bg-green-600 text-white px-4 py-2 rounded"
          >
            Withdraw
          </button>
        </div>
      </div>

      <div className="mt-8 bg-white p-6 rounded-lg shadow-md">
        <h2 className="text-lg font-bold">Transaction History</h2>

        {transactions.length > 0 ? (
          <table className="w-full mt-4 border-collapse border border-gray-300">
            <thead>
              <tr className="bg-gray-200">
                <th className="border border-gray-300 px-4 py-2">Date</th>
                <th className="border border-gray-300 px-4 py-2">Action</th>
                <th className="border border-gray-300 px-4 py-2">Amount</th>
                <th className="border border-gray-300 px-4 py-2">
                  Transaction ID
                </th>
              </tr>
            </thead>
            <tbody>
              {transactions.map((txn, index) => (
                <tr key={index} className="border border-gray-300">
                  <td className="px-4 py-2">
                    {new Date(txn.created_at).toLocaleString()}
                  </td>
                  <td className="px-4 py-2">{txn.action}</td>
                  <td className="px-4 py-2">{txn.amount} CB</td>
                  <td className="px-4 py-2">{txn.transaction_id}</td>
                </tr>
              ))}
            </tbody>
          </table>
        ) : (
          <p>No transactions available.</p>
        )}
      </div>

      {/* Modal for Withdrawal Processing */}
      {processingWithdrawal && (
        <div className="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
          <div className="bg-white p-6 rounded-lg shadow-lg text-center">
            <h2 className="text-lg font-bold">Processing Withdrawal</h2>
            <p>Please wait while we process your withdrawal...</p>
          </div>
        </div>
      )}
    </section>
  );
}

export default WalletPage;
