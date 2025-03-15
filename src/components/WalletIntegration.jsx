import React, { useMemo, useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import {
  ConnectionProvider,
  WalletProvider,
  useWallet,
} from "@solana/wallet-adapter-react";
import {
  PhantomWalletAdapter,
  SolflareWalletAdapter,
} from "@solana/wallet-adapter-wallets";
import {
  WalletModalProvider,
  WalletMultiButton,
} from "@solana/wallet-adapter-react-ui";
import "@solana/wallet-adapter-react-ui/styles.css";
import axios from "axios";
import { toast, ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";

function WalletIntegration({ onConnect }) {
  const wallets = useMemo(
    () => [new PhantomWalletAdapter(), new SolflareWalletAdapter()],
    []
  );
  const [usernameModal, setUsernameModal] = useState(false);
  const [username, setUsername] = useState("");
  const [walletAddress, setWalletAddress] = useState("");
  const [isConnected, setIsConnected] = useState(false);
  const navigate = useNavigate();

  const WalletConnectionChecker = () => {
      const { publicKey, connected, wallet } = useWallet();
    
      useEffect(() => {
        if (connected && publicKey) {
          const walletName = wallet?.adapter?.name || "Unknown Wallet";
          handleWalletConnection(publicKey.toString(), walletName);
        }
      }, [connected, publicKey]);
    
      return null;
    };
    
    const handleWalletConnection = async (address, walletName) => {
      try {
        const response = await axios.post(
          `${import.meta.env.VITE_API_URL}/connect-wallet`,
          {
            wallet_address: address,
          }
        );
        const { exists, user } = response.data;
    
        const expirationTime = new Date().getTime() + 20 * 24 * 60 * 60 * 1000; // 20 days
        localStorage.setItem(
          "walletData",
          JSON.stringify({
            walletAddress: address,
            walletName,
            expiresAt: expirationTime,
          })
        );
    
        if (exists) {
          onConnect(user);
          navigate("/play");
        } else {
          setWalletAddress(address);
          setUsernameModal(true);
        }
      } catch (error) {
        const errorMessage =
          error.response?.data?.message ||
          "Failed to connect wallet. Please try again.";
        toast.error(errorMessage);
        console.error("Error connecting wallet:", error);
      }
    };
    

  useEffect(() => {
    const walletData = JSON.parse(localStorage.getItem("walletData"));
    if (walletData) {
      const { walletAddress, expiresAt } = walletData;
      const currentTime = new Date().getTime();

      if (currentTime < expiresAt) {
        handleWalletConnection(walletAddress);
      } else {
        localStorage.removeItem("walletData");
      }
    }
  }, []);

  const handleUsernameSubmit = async () => {
    try {
      const response = await axios.post(
        `${import.meta.env.VITE_API_URL}/setUsername`,
        {
          wallet_address: walletAddress,
          username,
        }
      );
      setUsernameModal(false);
      toast.success("Username set successfully!");
      onConnect(response.data.user);
      navigate("/play");
    } catch (error) {
      const errorMessage =
        error.response?.data?.message ||
        "Failed to set username. Please try again.";
      toast.error(errorMessage);
    }
  };

  return (
    <ConnectionProvider endpoint="https://api.devnet.solana.com">
      <WalletProvider wallets={wallets} autoConnect>
        <WalletModalProvider>
          <WalletMultiButton />
          <WalletConnectionChecker />
          <ToastContainer />

          {usernameModal && (
            <div className="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
              <div className="max-w-md p-6 bg-white rounded-lg shadow-lg">
                <h2 className="mb-4 text-xl font-semibold text-gray-800">
                  Set Your Username
                </h2>
                <p className="mb-6 text-sm text-gray-600">
                  Welcome! Enter a username to complete your wallet setup.
                </p>
                <input
                  type="text"
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  placeholder="Enter your username"
                  className="w-full px-4 py-2 mb-4 text-gray-800 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                />
                <div className="flex justify-end space-x-4">
                  <button
                    onClick={() => setUsernameModal(false)}
                    className="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-200 rounded-lg hover:bg-gray-300 focus:ring-2 focus:ring-gray-300"
                  >
                    Cancel
                  </button>
                  <button
                    onClick={handleUsernameSubmit}
                    className="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-400"
                  >
                    Submit
                  </button>
                </div>
              </div>
            </div>
          )}
        </WalletModalProvider>
      </WalletProvider>
    </ConnectionProvider>
  );
}

export default WalletIntegration;
