import React, { useState, useEffect } from "react";
import Header from "../components/Header/index";
import { useNavigate } from "react-router-dom";
import Sidebar from "../components/sidebar/index";
import AppProvider, { useAppContext } from "../components/context/AppContext";

function LayoutContent({ children }) {
  const { user, loading } = useAppContext();
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const navigate = useNavigate();

  useEffect(() => {
    const walletData = JSON.parse(localStorage.getItem("walletData"));

    if (walletData) {
      const { expiresAt } = walletData;
      const currentTime = new Date().getTime();

      if (currentTime >= expiresAt) {
        // Wallet expired, clear localStorage and navigate to connect/home
        localStorage.removeItem("walletData");
        navigate("/connect");
      }
    } else {
      navigate("/connect");
    }
  }, [navigate]);

  if (loading) {
    return <div>Loading...</div>; // You can replace this with a spinner or loading animation
  }

  return (
    <div className="dark:bg-boxdark-2 dark:text-bodydark">
      {/* Page Wrapper */}
      <div className="flex h-screen overflow-hidden">
        {/* Sidebar */}
        <Sidebar user={user} sidebarOpen={sidebarOpen} setSidebarOpen={setSidebarOpen} />
        {/* Content Area */}
        <div className="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden">
          {/* Header */}
          <Header user={user} sidebarOpen={sidebarOpen} setSidebarOpen={setSidebarOpen} />
          {/* Main Content */}
          <main>
            <div className="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
              {children}
            </div>
          </main>
        </div>
      </div>
    </div>
  );
}

export default function DefaultLayout({ children }) {
  return (
    <AppProvider>
      <LayoutContent>{children}</LayoutContent>
    </AppProvider>
  );
}
