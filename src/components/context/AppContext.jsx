// src/context/AppContext.js
import React, { createContext, useContext, useState, useEffect } from "react";
// import Cookies from "js-cookie";

// Create context
const AppContext = createContext();

// AppProvider to wrap around the protected pages and share global data
// export
 const AppProvider = ({ children }) => {

  const [unreadInboxCount, setUnreadInboxCount] = useState(null);
  const [unreadRequestCount, setUnreadRequestCount] = useState(null);
  const [unreadNotificationCount, setUnreadNotificationCount] = useState(null);
  const [unResolvedTicketCount, setUnResolvedTicketCount] = useState(null);
  const [user, setUserData] = useState({
    fullname: '',
    email: '',
});

  useEffect(() => {

    
    // const jwtToken = Cookies.get('jwtToken');
    const jwtToken= "55";
    // Fetch unread notifications count
    const fetchUnreadNotifications = async () => {
        try {
          const response = await fetch('http://localhost:8001/api/getSidebarDetails', {
            method: 'GET',
            headers: {
              'X_ACCESS_TOKEN': jwtToken,
            },
          });
          const result = await response.json();
          const unreadCount = result.data.unreadInbox;
          const unreadRequest = result.data.unreadConnectionRequest;
          const unreadNotifications = result.data.unreadNotifications;
          const unResolvedTickets = result.data.unResolvedTickets;
  
          setUnreadRequestCount(unreadRequest || null);
          setUnreadInboxCount(unreadCount || null);
          setUnreadNotificationCount(unreadNotifications || null);
          setUnResolvedTicketCount(unResolvedTickets || null);
          setUserData(result.data.user || { fullname: '', email: '' });
  
        } catch (error) {
          console.error("Error fetching unread inbox count:", error);
          setUnreadInboxCount(null); // Set to an empty object in case of error
        }
  
      };

   
    fetchUnreadNotifications();
    // fetchUserData();
  }, []);

  return (
    <AppContext.Provider value={{ unreadNotificationCount, setUnreadNotificationCount, unreadRequestCount, setUnreadRequestCount, unreadInboxCount, setUnreadInboxCount, unResolvedTicketCount, setUnResolvedTicketCount, user, setUserData }}>
      {children}
    </AppContext.Provider>
  );
};

// Custom hook to access the AppContext
// export const useAppContext = () => useContext(AppContext);
export const useAppContext = () => {
  const context = useContext(AppContext);
  if (!context) {
    throw new Error("useAppContext must be used within an AppProvider");
  }
  return context;
};


export default AppProvider; // Optional default export