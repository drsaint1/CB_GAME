import React from "react";
import { Link } from "@remix-run/react";

const SidebarProButton = () => {
  return (
    <div className="bg-gray-800 p-4 rounded-lg text-center shadow-md border border-[#1C2434] mx-5 sticky top-10">
      <h2 className="text-white text-lg font-semibold">Bucxai Pro</h2>
       <p className="text-gray text-sm"> Enjoy all features as a professional,enterprise </p>
       <Link to="/upgrade" className="mt-4 inline-block bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 transition duration-300 " > Upgrade </Link>
    </div>
  );
};

export default SidebarProButton;
