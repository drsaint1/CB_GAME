import React, { useState } from 'react';
import { Link, useLocation } from "@remix-run/react"; // Use Remix's Link and useLocation
import { IoChevronDown } from "react-icons/io5"; // Import react-icons
// import SidebarDropdown from "~/components/Sidebar/SidebarDropdown"; // Adjust import based on your project structure
import SidebarDropdown from "./SidebarDropdown";
import LogoutModal from "../utilities/modals/logout";

const SidebarItem = ({ item, pageName, setPageName,onLogout }) => {


  const location = useLocation(); // Get current pathname using useLocation

 
  const handleClick = () => {
    const updatedPageName =
      pageName !== item.label.toLowerCase() ? item.label.toLowerCase() : "";
    setPageName(updatedPageName);

    if(item.action){
      item.action();
    }

    // if (item.label === "Log Out") {
    //   onLogout(); // Trigger the logout modal
    // }
  };

  

  const isActive = (item) => {
    if (item.route === location.pathname) return true;
    if (item.children) {
      return item.children.some((child) => isActive(child));
    }
    return false;
  };

  const isItemActive = isActive(item);

  // const toggleLogoutModal = () => {
  //   setIsLogoutModal(!isLogoutModalOpen);
  // };

  return (
    <>
      <li>
        <Link
         to={item.route !== "Log Out" ? item.route : "#"}
        // to={item.route} // Use 'to' for Remix Link
          onClick={handleClick}
          className={`${isItemActive ? "bg-graydark dark:bg-meta-4" : ""} group relative flex items-center gap-2.5 rounded-sm px-4 py-2 font-medium text-bodydark1 duration-300 ease-in-out hover:bg-graydark dark:hover:bg-meta-4`}
        >
          {item.icon}
          {item.label}
          {item.ping && <div className="absolute right-1 bg-red text-white text-xs font-bold p-1 h-5 w-5 rounded-full flex items-center justify-center"> {item.ping}</div> }
          {item.children && (
            <IoChevronDown
              className={`absolute right-8 top-1/2 -translate-y-1/2 fill-current ${
                pageName === item.label.toLowerCase() && "rotate-180"
              }`}
              size={20}
            />
          )}
        </Link>
{/* 
        {item.action && (
          onClick={item.action}
        )} */}

        {item.children && (
          <div
            className={`translate transform overflow-hidden ${
              pageName !== item.label.toLowerCase() && "hidden"
            }`}
          >
            <SidebarDropdown item={item.children} />
          </div>
        )}
      </li>

    </>
  );
};

export default SidebarItem;
