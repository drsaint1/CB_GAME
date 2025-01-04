import React from "react";
import { Link, useLocation } from "@remix-run/react"; // Remix's Link and useLocation

const SidebarDropdown = ({ item }) => {
  const location = useLocation(); // Get the current location (pathname)

  return (
    <>
      <ul className="mb-5.5 mt-4 flex flex-col gap-2.5 pl-6">
        {item.map((item, index) => (
          <li key={index}>
            <Link
              to={item.route} // Use 'to' for Remix's Link
              className={`group relative flex items-center gap-2.5 rounded-md px-4 font-medium text-bodydark2 duration-300 ease-in-out hover:text-white ${
                location.pathname === item.route ? "text-white" : ""
              }`}
            >
               {item.icon}
              {item.label}
              {item.ping && <div className="absolute right-1 bg-red text-white text-xs font-bold p-1 h-5 w-5 rounded-full flex items-center justify-center"> {item.ping}</div> }
            </Link>
          </li>
        ))}
      </ul>
    </>
  );
};

export default SidebarDropdown;
