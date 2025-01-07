import React from "react";
import { Link, useLocation } from "react-router-dom";
import { IoClose } from "react-icons/io5";
import ClickOutside from "../ClickOutside";
import SidebarItem from "./SidebarItem";
import SidebarProButton from "./SidebarProButton";
import { useAppContext } from "../context/AppContext";

import { FaTachometerAlt, FaCalendarAlt, FaUser, FaWpforms, FaTable, FaCog, FaChartBar, FaCube } from "react-icons/fa"; // Import React Icons
import { RiNotification2Line, RiNotification2Fill, RiRobot2Fill, RiRobot2Line, RiRobot3Fill, RiRobot3Line, RiHome7Line, RiHome7Fill, RiAccountBoxFill, RiAccountBoxLine, RiLogoutCircleLine } from "react-icons/ri";
import { IoMailSharp, IoMailOutline, IoMailUnreadOutline, IoTicketOutline } from "react-icons/io5";

const Sidebar = ({ sidebarOpen, setSidebarOpen, setIsLogoutModalOpen }) => {
  const { unreadNotificationCount, unreadInboxCount } = useAppContext(); // Consume context values
  // const location = useLocation(); // Get the current route path

  const menuGroups = [
    {
      name: "OVERVIEW",
      menuItems: [
        {
          icon: <RiHome7Line style={{ fontSize: "1.5em" }} />,
          label: "Dashboard",
          route: "/dashboard",
        },
        {
          icon: <RiRobot3Line style={{ fontSize: "1.5em" }} />,
          label: "Create Chat Widget",
          route: "/create",
        },
        {
          icon: <RiRobot2Line style={{ fontSize: "1.5em" }} />,
          label: "Deploy Chat Widget",
          route: "/bots",
        },
        {
          icon: <IoMailOutline style={{ fontSize: "1.5em" }} />,
          label: "Inbox",
          route: "/inbox",
          ping: unreadInboxCount,
        },
      ],
    },
    {
      name: "ACCOUNTS",
      menuItems: [
        {
          icon: <RiNotification2Line style={{ fontSize: "1.5em" }} />,
          label: "Notifications",
          route: "/notifications",
          ping: unreadNotificationCount,
        },
        {
          icon: <RiAccountBoxLine style={{ fontSize: "1.5em" }} />,
          label: "Profile",
          route: "/profile",
        },
        {
          icon: <RiLogoutCircleLine style={{ fontSize: "1.5em" }} />,
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
        className={`fixed left-0 top-0 z-50 flex h-screen w-72 flex-col bg-black transition-transform ${
          sidebarOpen ? "translate-x-0" : "-translate-x-full"
        }`}
      >
        <div className="flex items-center justify-between px-6 py-4">
          <Link to="/" className="flex items-center gap-4 text-white">
            <img
              width={42}
              height={42}
              src="/images/logo/blackbucxai.jpg"
              alt="Logo"
              className="rounded-xl"
            />
            <span className="text-2xl font-bold">CB GAME</span>
          </Link>
          <button
            onClick={() => setSidebarOpen(!sidebarOpen)}
            className="text-white"
          >
            <IoClose className="text-3xl" />
          </button>
        </div>
        <nav className="mt-4 px-4">
          {menuGroups.map((group, groupIndex) => (
            <div key={groupIndex}>
              <h3 className="mb-2 text-sm font-semibold text-gray-400">
                {group.name}
              </h3>
              <ul>
                {group.menuItems.map((menuItem, menuIndex) => (
                  <SidebarItem
                    key={menuIndex}
                    item={menuItem}
                    setIsLogoutModalOpen={setIsLogoutModalOpen}
                  />
                ))}
              </ul>
            </div>
          ))}
        </nav>
        <SidebarProButton />
      </aside>
    </ClickOutside>
  );
};

export default Sidebar;
