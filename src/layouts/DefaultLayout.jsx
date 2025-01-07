// import React, { useState, useEffect, useRef, useCallback } from "react";
// import Sidebar from "../sidebar";
// import Header from "../header";
// // import LogoutModal from "../utilities/modals/logout";
// // import Cookies from "js-cookie";
// import { useParams, useNavigate } from 'react-router-dom';
// // import { AppProvider } from "../context/AppContext";
// import AppProvider from "../context/AppContext";

// export default function DefaultLayout({ children }) {
//   const [sidebarOpen, setSidebarOpen] = useState(false);
//   const [isLogoutModalOpen, setIsLogoutModalOpen] = useState(false); // Manage modal state
//   const [userToken, setUserToken] = useState(null);
//   // const navigate = useNavigate();

//   // Function to handle opening the logout modal
//   const handleLogoutModal = () => {
//     setIsLogoutModalOpen(true);
//   };


//   // useEffect(() => {
//   //   const jwtToken = Cookies.get('jwtToken');
//   //   setUserToken(jwtToken);
//   //   if (!jwtToken) {
//   //     navigate('/login');
//   //     return;
//   //   }
//   // }, [navigate]);



//   return (
//     <AppProvider>
//       <div className="flex">
//         {/* Sidebar */}
//         <Sidebar sidebarOpen={sidebarOpen} setSidebarOpen={setSidebarOpen} onLogout={handleLogoutModal} isLogoutModalOpen={isLogoutModalOpen} setIsLogoutModalOpen={setIsLogoutModalOpen} userToken={userToken} />

//         {/* Content Area */}
//         <div className="relative flex flex-1 flex-col lg:ml-72.5">
//           {/* Header */}
//           <Header sidebarOpen={sidebarOpen} setSidebarOpen={setSidebarOpen} />

//           {/* Main Content */}
//           <main className="mb-20">
//             {/* <div className="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10 "> */}
//             <div className="mx-auto max-w-screen-2xl p-2 md:p-6 2xl:p-5 ">
//               {children}
//             </div>
//           </main>

//         </div>

//         {/* Logout Modal */}
//         {isLogoutModalOpen && (
//           <LogoutModal onClose={() => setIsLogoutModalOpen(false)} />
//         )}


//       </div>
//     </AppProvider>
//   );
// }


import React, { useState, ReactNode } from 'react';
import Header from '../components/Header/index';
// import Sidebar from '../components/Sidebar/index';
import Sidebar from '../components/sidebar/indexx';

export default function DefaultLayout({ children }) {
  const [sidebarOpen, setSidebarOpen] = useState(false);

  return (
    <div className="dark:bg-boxdark-2 dark:text-bodydark">
      {/* <!-- ===== Page Wrapper Start ===== --> */}
      <div className="flex h-screen overflow-hidden">
        {/* <!-- ===== Sidebar Start ===== --> */}
        <Sidebar sidebarOpen={sidebarOpen} setSidebarOpen={setSidebarOpen} />
        {/* <!-- ===== Sidebar End ===== --> */}

        {/* <!-- ===== Content Area Start ===== --> */}
        <div className="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden">
          {/* <!-- ===== Header Start ===== --> */}
          <Header sidebarOpen={sidebarOpen} setSidebarOpen={setSidebarOpen} />
          {/* <!-- ===== Header End ===== --> */}

          {/* <!-- ===== Main Content Start ===== --> */}
          <main>
            <div className="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
              {children}
            </div>
          </main>
          {/* <!-- ===== Main Content End ===== --> */}
        </div>
        {/* <!-- ===== Content Area End ===== --> */}
      </div>
      {/* <!-- ===== Page Wrapper End ===== --> */}
    </div>
  );
};

