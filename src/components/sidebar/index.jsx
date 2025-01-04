import React, { useEffect, useRef, useState } from "react";
import { Link, useLocation } from "@remix-run/react"; // Remix-specific imports
import { FaTachometerAlt, FaCalendarAlt, FaUser, FaWpforms, FaTable, FaCog, FaChartBar, FaCube } from "react-icons/fa"; // Import React Icons
import { RiNotification2Line, RiNotification2Fill, RiRobot2Fill, RiRobot2Line, RiRobot3Fill, RiRobot3Line, RiHome7Line, RiHome7Fill, RiAccountBoxFill, RiAccountBoxLine, RiLogoutCircleLine } from "react-icons/ri";
import { BsDatabaseFillGear, BsDatabaseGear } from "react-icons/bs";
import { SiLivechat } from "react-icons/si";
import { PiBrainLight, PiBrainFill } from "react-icons/pi";
import { BiSolidConversation, BiConversation, BiExtension, BiSolidExtension } from "react-icons/bi";
import { SiGoogleanalytics } from "react-icons/si";
import { TbBrandGoogleAnalytics } from "react-icons/tb";
import { MdOutlineFeedback, MdFeedback } from "react-icons/md";
import { IoMailSharp, IoMailOutline, IoMailUnreadOutline, IoTicketOutline } from "react-icons/io5";
import SidebarItem from "./SidebarItem";
import ClickOutside from "../ClickOutside";
import useLocalStorage from "../../hooks/useLocalStorage";
import SidebarProButton from "./sidebarProbutton";
import { IoClose } from "react-icons/io5";
import { IoSettings, IoSettingsOutline } from "react-icons/io5";
import axios from 'axios';
import Cookies from "js-cookie";
import LogoutModal from "../utilities/modals/logout";
import { useAppContext } from "../context/AppContext";


const Sidebar = ({ sidebarOpen, setSidebarOpen, onLogout, isLogoutModalOpen, setIsLogoutModalOpen, userToken }) => {

  const {  unreadNotificationCount, setUnreadNotificationCount, unreadRequestCount, setUnreadRequestCount, unreadInboxCount, setUnreadInboxCount, unResolvedTicketCount, setUnResolvedTicketCount } = useAppContext(); // Consume unread notification count from context

  const location = useLocation();
  const [pageName, setPageName] = useLocalStorage("selectedMenu", "dashboard");
  const [inboxPing, setInboxPing] = useState(null);
  const socketRef = useRef(null);
  const [fromUserId, setFromUserId] = useState(5);
  const [connectionRequestCount, setConnectionRequestCount] = useState(0);
  



  

  function updateRequestStatus(userId, status) {
    // Update the UI to reflect the accepted/rejected status
    setRequests((prevRequests) =>
      prevRequests.map((req) =>
        req.from_user === userId ? { ...req, status: status } : req
      )
    );
  }




  const menuGroups = [
    {
      name: "OVERVIEW",
      menuItems: [
        {
          icon: <RiHome7Line style={{ fontSize: '1.5em' }} />,
          label: "Dashboard",
          route: '/dashboard',
          // route: "#",
          // children: [{ label: "eCommerce", route: "/" }],
        },
        {
          icon: <RiRobot3Line style={{ fontSize: '1.5em' }} />,
          label: "Create Chat Widget",
          route: "/create",
        },
        {
          icon: <RiRobot2Line style={{ fontSize: '1.5em' }} />,
          label: "Deploy Chat Widget",
          route: "/bots",
        },
        {
          icon: <IoMailOutline style={{ fontSize: '1.5em' }} />,
          label: "Inbox",
          route: "/inbox",
          ping: unreadInboxCount,
          // route: "#",
          // children: [
          //   { label: "Missed Message Request", route: "/inbox" },
          //   { label: "Messages", route: "/ui/buttons" },
          // ],
        },

      ]
    },
    {
      name: "LIVECHAT DESK",
      menuItems: [
        {
          icon: <IoTicketOutline style={{ fontSize: '1.5em' }} />,
          label: "Tickets",
          route: "/tickets",
          ping: unResolvedTicketCount,
        },
        {
          icon: <SiLivechat style={{ fontSize: '1.5em' }} />,
          label: "LiveChat ",
          ping: unreadRequestCount,
          route: "#",
          children: [
            { label: "Settings", route: "/livechat", icon: <RiRobot2Line style={{ fontSize: '1.5em' }} /> },
            { label: "Chat Requests", route: "/request", icon: <IoMailUnreadOutline style={{ fontSize: '1.5em' }} />, ping: unreadRequestCount, },
          ],
        },

      ],
    },
    {
      name: "AI BOT STUDIO",
      menuItems: [
        {
          icon: <BsDatabaseGear style={{ fontSize: '1.5em' }} />,
          label: "Training",
          route: "/train",
        },
        {
          icon: <PiBrainLight style={{ fontSize: '1.5em' }} />,
          label: "Knowledge Store",
          route: "/knowledge",
        },
      ],
    },

    {
      name: "TOOLS & ANALYTICS",
      menuItems: [
        {
          icon: <FaWpforms style={{ fontSize: '1.5em' }} />,
          label: "Leads",
          route: "/leads",
        },
        // {
        //   icon: <BiConversation  style={{ fontSize: '1.5em' }} />,
        //   label: "Conversations",
        //   route: "#",
        //   children: [
        //     { label: "AI Conversations", route: "/forms/form-elements" },
        //     { label: "Livechat Conversations", route: "/forms/form-layout" },
        //   ],
        // },
        {
          icon: <BiExtension style={{ fontSize: '1.5em' }} />,
          label: "Integrations",
          route: "#",
          children: [
            { label: "Web Integrations", route: "/web_integrations", icon: <RiRobot2Line style={{ fontSize: '1.5em' }} /> },
            { label: "Platform Integrations", route: "/integrations", icon: <IoMailUnreadOutline style={{ fontSize: '1.5em' }} /> },
            { label: "Deployment Status", route: "/deployments", icon: <IoMailUnreadOutline style={{ fontSize: '1.5em' }} /> },
          ],
          // route: "/integrations",
        },
        {
          icon: <TbBrandGoogleAnalytics style={{ fontSize: '1.5em' }} />,
          label: "Analytics",
          route: "/analytics",
        },
      ],
    },

    {
      name: "ACCOUNTS",
      menuItems: [

        {
          icon: <RiNotification2Line style={{ fontSize: '1.5em' }} />,
          label: "Notifications",
          route: "/notifications",
          ping : unreadNotificationCount,
        },
        {
          icon: <RiAccountBoxLine style={{ fontSize: '1.5em' }} />,
          label: "Profile",
          route: "/profile",
          // children: [
          //   { label: "Profile Details", route: "/profile" },
          //   { label: "Edit Profile", route: "/edit-profile" },
          // ],
        },
        // {
        //   icon: <IoSettingsOutline style={{ fontSize: '1.5em' }} />,
        //   label: "Settings",
        //   route: "#",
        //   onClick: () => setIsLogoutModalOpen(true),
        // },
        {
          icon: <MdOutlineFeedback style={{ fontSize: '1.5em' }} />,
          label: "Feedback & Reports",
          route: "/feedback",
        },
        {
          icon: <RiLogoutCircleLine style={{ fontSize: '1.5em' }} />,
          label: "Log Out",
          route: "#",
          action: () => setIsLogoutModalOpen(true),
        },
      ],
    },
  ];


  return (
    <ClickOutside onClick={() => setSidebarOpen(false)}>
      <aside
        className={`fixed left-0 top-0 z-999 flex h-screen w-72.5 flex-col overflow-y-hidden bg-black duration-300 ease-linear dark:bg-boxdark lg:translate-x-0 ${sidebarOpen ? "translate-x-0" : "-translate-x-full"
          }`}
      >
        {/* <!-- SIDEBAR HEADER --> */}
        <div className="flex items-center justify-between gap-2 px-6 py-5.5 lg:py-6.5">
          <Link to="/" className="flex items-center justify-center gap-4"> <img width={42} height={42} src={"/images/logo/blackbucxai.jpg"} alt="Logo" priority="true" className="rounded-xl" /> <span className="text-white font-bold text-4xl">Bucxai</span> </Link>

          <button onClick={() => setSidebarOpen(!sidebarOpen)} aria-controls="sidebar" className="block lg:hidden text-white" > <IoClose className="text-4xl text-white" /> </button>
        </div>
        {/* <!-- SIDEBAR HEADER --> */}

        <div className="no-scrollbar flex flex-col overflow-y-auto duration-300 ease-linear">
          {/* <!-- Sidebar Menu --> */}
          <nav className="mt-5 px-4 py-4 lg:mt-9 lg:px-6">
            {menuGroups.map((group, groupIndex) => (
              <div key={groupIndex}>
                <h3 className="mb-4 ml-4 text-sm font-semibold text-bodydark2"> {group.name} </h3>

                <ul className="mb-6 flex flex-col gap-1.5">
                  {group.menuItems.map((menuItem, menuIndex) => (
                    <SidebarItem
                      key={menuIndex}
                      // item={menuItem}
                      item={{ ...menuItem, action: menuItem.label === "Log Out" ? () => setIsLogoutModalOpen(true) : menuItem.action, }}
                      pageName={pageName}
                      setPageName={setPageName}
                      onLogout={onLogout} 
                    />
                  ))}
                </ul>
              </div>
            ))}
          </nav>

          {/* <!-- Sidebar Menu --> */}
        </div>
        <SidebarProButton />
      </aside>
    </ClickOutside>
  );
};

export default Sidebar;
