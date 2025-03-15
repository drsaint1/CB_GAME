// BearDodgeGame.jsx
import React, { useRef, useEffect, useState, useMemo } from "react";
import "../style/games.css";
import {
  useInitializeSession,
  usePurchaseShield,
  useWithdrawTokens,
  useSavePoints,
  useContinueGame,
  initializeProgram,
  useWalletData,
  useInitializeConfig,
  useCheckBalance,
} from "../hooks/useContracts";
import { getAssociatedTokenAddress } from "@solana/spl-token";
import { PublicKey, Connection } from "@solana/web3.js";
import { CB_TOKEN_MINT, PROGRAM_ADDRESS, VAULT_ADDRESS } from "../constants";
import { useWallet } from "@solana/wallet-adapter-react";

function WalletPage() {
  const [amount, setAmount] = useState("");
  const [errorMessage, setErrorMessage] = useState("");
  const [loading, setLoading] = useState(false);
  const [sessionPDA, setSessionPDA] = useState(null);
  const [configPDA, setConfigPDA] = useState(null);

  // Load wallet data from localStorage
  const walletData = JSON.parse(localStorage.getItem("walletData"));
  if (!walletData) return <div>No wallet data found!</div>;
  const userPublicKey = new PublicKey(walletData.walletAddress);
  const { wallet } = useWallet();
  const [programState, setProgramState] = useState(null);

  // On-chain hooks
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

  const { initializeSession } = useInitializeSession(program);
  const { initializeConfig } = useInitializeConfig(program);
  const { withdrawTokens } = useWithdrawTokens(program);
  const { checkBalance, loading: loadingBalance, error, balance } = useCheckBalance(program);

  const { balance: walletBalance, transactions, fetchWalletData } =
    useWalletData(userPublicKey);
  console.log("user transaction", transactions);

  useEffect(() => {
    if (programState) {
      fetchWalletData();
      initializeSession()
        .then((pda) => {
          initializeConfig(10, 2, 5).then((config) => {
            setSessionPDA(pda);
            checkBalance(pda);
            setConfigPDA(config);
          });
        })
        .catch((err) => {
          console.error("Error initializing session:", err);
          setErrorMessage("Error initializing session.");
        });
    }
  }, [programState]);

  const handleWithdraw = async () => {
    try {
      setLoading(true);
      setErrorMessage("");
      if (!program) throw new Error("Program not initialized!");
      const txId = await withdrawTokens(sessionPDA, amount);
      alert("Tokens withdrawn. Tx ID: " + txId);
      fetchWalletData();
    } catch (error) {
      setErrorMessage(error.message);
    } finally {
      setLoading(false);
      setAmount("");
    }
  };

  return (
    <section className="container mx-auto p-6">
      <div className="bg-blue-100 dark:bg-blue-900 border border-blue-300 dark:border-blue-700 rounded-lg p-6 shadow-md">
        <h1 className="text-2xl font-bold">My Wallet</h1>
        <p>Manage your wallet balance and transactions.</p>
      </div>

      {errorMessage && (
        <div className="bg-red-100 text-red-800 p-4 mt-4">{errorMessage}</div>
      )}

      {loading ? (
        <div className="text-center mt-6">Loading...</div>
      ) : (
        <>
          <div className="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="bg-white p-6 rounded-lg shadow-md">
              <h2 className="text-lg font-bold">Current Balance</h2>
              <p className="text-3xl text-blue-600 mt-4">
                {balance || "0.00"} CB
              </p>
            </div>
            <div className="bg-white p-6 rounded-lg shadow-md">
              <h2 className="text-lg font-bold">Withdraw Tokens</h2>
              <input
                type="number"
                value={amount}
                onChange={(e) => setAmount(e.target.value)}
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
            {transactions.length ? (
              <table className="w-full mt-4">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  {transactions.map((txn, index) => (
                    <tr key={index}>
                      <td>{txn.date}</td>
                      <td>{txn.description}</td>
                      <td>{txn.amount}</td>
                      <td>{txn.status}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            ) : (
              <p>No transactions available.</p>
            )}
          </div>
        </>
      )}
    </section>
  );
}

export default WalletPage;
