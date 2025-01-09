import React, { createContext, useContext, useState, useEffect } from "react";

// Create context
const AppContext = createContext();

// AppProvider to wrap around the application and manage global state
export const AppProvider = ({ children }) => {
  const [user, setUserData] = useState(null); // Store user details
  const [usernameModal, setUsernameModal] = useState(false); // Control the username modal
  const [username, setUsername] = useState(""); // Store username input

  useEffect(() => {
    const walletData = JSON.parse(localStorage.getItem("walletData"));

    if (walletData) {
      const { walletAddress, expiresAt } = walletData;
      const currentTime = new Date().getTime();

      if (currentTime < expiresAt) {
        // Wallet is valid; fetch user details
        const fetchUserDetails = async () => {
          try {
            const response = await fetch(
              `http://127.0.0.1:8000/api/getUserDetails?walletAddress=${walletAddress}`
            );
            const result = await response.json();

            if (result.success) {
              setUserData(result.data);

              // Check if username is missing
              if (!result.data.username) {
                setUsernameModal(true);
              }
            } else {
              console.error("Error fetching user details:", result.message);
            }
          } catch (error) {
            console.error("Error fetching user details:", error);
          }
        };

        fetchUserDetails();
      } else {
        // Wallet expired, clear storage
        localStorage.removeItem("walletData");
      }
    }
  }, []);

  const handleUsernameSubmit = async () => {
    try {
      const response = await fetch(
        `http://127.0.0.1:8000/api/setUsername`, 
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ walletAddress: user.wallet_address, username }),
        }
      );

      const result = await response.json();

      if (result.success) {
        setUserData((prev) => ({ ...prev, username }));
        setUsernameModal(false);
      } else {
        console.error("Error setting username:", result.message);
      }
    } catch (error) {
      console.error("Error setting username:", error);
    }
  };

  return (
    <AppContext.Provider value={{ user, setUserData }}>
      {children}

      {usernameModal && (
        <div className="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
          <div className="max-w-md p-6 bg-white rounded-lg shadow-lg">
            <h2 className="mb-4 text-xl font-semibold text-gray-800"> Set Your Username </h2>
            <p className="mb-6 text-sm text-gray-600">
              Welcome! Enter a username to complete your wallet setup.
            </p>
            <input type="text" value={username} onChange={(e) => setUsername(e.target.value)} placeholder="Enter your username" className="w-full px-4 py-2 mb-4 text-gray-800 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" />
            <div className="flex justify-end space-x-4">
              <button onClick={() => setUsernameModal(false)} className="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-200 rounded-lg hover:bg-gray-300 focus:ring-2 focus:ring-gray-300" > Cancel </button>
              <button onClick={handleUsernameSubmit} className="px-4 py-2 text-sm font-medium text-black bg-blue rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-400" > Submit </button>
            </div>
          </div>
        </div>
      )}
    </AppContext.Provider>
  );
};

// Custom hook to access the AppContext
export const useAppContext = () => {
  const context = useContext(AppContext);
  if (!context) {
    throw new Error("useAppContext must be used within an AppProvider");
  }
  return context;
};

export default AppProvider;
