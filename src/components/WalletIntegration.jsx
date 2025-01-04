import React, { useMemo, useEffect,useState } from "react";

import { ConnectionProvider, WalletProvider, useWallet, } from "@solana/wallet-adapter-react";
import { PhantomWalletAdapter, SolflareWalletAdapter, } from "@solana/wallet-adapter-wallets";
import { WalletModalProvider, WalletMultiButton, } from "@solana/wallet-adapter-react-ui";
import "@solana/wallet-adapter-react-ui/styles.css";
import axios from "axios";

function WalletIntegration({ onConnect }) {
  const wallets = useMemo(
    () => [new PhantomWalletAdapter(), new SolflareWalletAdapter()],
    []
  );

  const [usernameModal, setUsernameModal] = useState(false);
  const [username, setUsername] = useState("");
  const [walletAddress, setWalletAddress] = useState("");
  const [isConnected, setIsConnected] = useState(false); // Prevent infinite requests



  const WalletConnectionChecker = () => {
    const { publicKey, connected } = useWallet();

    useEffect(() => {
      if (connected && publicKey && !isConnected) {
        setIsConnected(true); // Prevent further requests
        handleWalletConnection(publicKey.toString());
      }
    }, [connected, publicKey, isConnected]);

    return null;
  };

  const handleWalletConnection = async (address) => {
    try {
      const response = await axios.post("http://127.0.0.1:8000/api/connect-wallet", {
        wallet_address: address,
      });
      const { exists, user } = response.data;

      // Save wallet address and expiration time to localStorage
      const expirationTime = new Date().getTime() + 20 * 24 * 60 * 60 * 1000; // 20 days
      localStorage.setItem("walletData", JSON.stringify({ walletAddress: address, expiresAt: expirationTime }));

      if (exists) {
        // localStorage.setItem("walletAddress", address); // Save wallet address in localStorage
        // Wallet exists, fetch user data
        onConnect(user);
      } else {
        // Wallet is new, show username modal
        setWalletAddress(address);
        setUsernameModal(true);
      }
    } catch (error) {
      console.error("Error connecting wallet:", error);
    }
  };

   // Check localStorage on mount
   useEffect(() => {
    const walletData = JSON.parse(localStorage.getItem("walletData"));
    if (walletData) {
      const { walletAddress, expiresAt } = walletData;
      const currentTime = new Date().getTime();

      if (currentTime < expiresAt) {
        // Wallet is still valid
        handleWalletConnection(walletAddress);
      } else {
        // Wallet expired, clear localStorage
        localStorage.removeItem("walletData");
      }
    }
  }, []);

  const handleUsernameSubmit = async () => {
    try {
      const response = await axios.post("http://127.0.0.1:8000/api/set-username", {
        wallet_address: walletAddress,
        username,
      });

      setUsernameModal(false);
      onConnect(response.data.user);
    } catch (error) {
      console.error("Error setting username:", error);
    }
  };



  // const WalletConnectionChecker = () => {
  //   const { connected } = useWallet();

  //   useEffect(() => {
  //     if (connected) {
  //       onConnect(); // Notify parent when the wallet is connected
  //     }
  //   }, [connected]);

  //   return null; // No visual output, only logic
  // };

  return (
    <ConnectionProvider endpoint="https://api.devnet.solana.com">
      <WalletProvider wallets={wallets}>
        {/* Removed autoConnect */}
        <WalletModalProvider>
          <WalletMultiButton />
          <WalletConnectionChecker />

            {/* Username Modal */}
            {usernameModal && (
            <div className="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
              <div className=" max-w-md p-6 bg-white rounded-lg shadow-lg">
                <h2 className="mb-4 text-xl font-semibold text-gray-800"> Set Your Username </h2>
                <p className="mb-6 text-sm text-gray-600"> Welcome! Enter a username to complete your wallet setup. </p>
                <input type="text" value={username} onChange={(e) => setUsername(e.target.value)} placeholder="Enter your username" className="w-full px-4 py-2 mb-4 text-gray-800 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" />
                <div className="flex justify-end space-x-4">
                  <button onClick={() => setUsernameModal(false)} className="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-200 rounded-lg hover:bg-gray-300 focus:ring-2 focus:ring-gray-300" > Cancel </button>
                  <button onClick={handleUsernameSubmit} className="px-4 py-2 text-sm font-medium text-black bg-blue rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-400" > Submit </button>
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
