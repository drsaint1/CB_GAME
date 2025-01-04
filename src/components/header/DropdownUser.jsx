import { useState,useEffect } from "react";
import { Link } from "react-router-dom";
import { FiChevronDown, FiUser, FiPhone, FiSettings } from "react-icons/fi";
// import Avatar from "../utilities/avatar";
// import Cookies from "js-cookie";
// import { useAppContext } from "../context/AppContext";

const DropdownUser = () => {
  const [dropdownOpen, setDropdownOpen] = useState(false);
  const [authname, setAuthName] = useState("");

  const {   user } = useAppContext();

  const userr = {
    name: "Olarinde Bukunmi",
    image: null, // This would be null if the user didn't upload an image
  };

  // useEffect(() => {
  //   const cookieName = Cookies.get('na');
  //   setAuthName(user.fullname || "name unavailbale");  // Set state after hydration
  // }, []);

  return (
    <div onClick={() => setDropdownOpen(false)} className="relative">
      <Link
        onClick={(e) => {
          e.preventDefault();
          setDropdownOpen(!dropdownOpen);
        }}
        className="flex items-center gap-4"
        to="#"
      >
        <span className="hidden text-right lg:block">
          <span className="block text-sm font-medium text-black dark:text-white">
          {user.fullname || ""}
          </span>
          <span className="block text-xs">{user.category}</span>
        </span>

        <span className="h-12 w-12 rounded-full">
          {/* <img
            src="/images/user/user-01.png"
            alt="User"
            className="object-cover w-full h-full"
          /> */}
           {/* <Avatar name={user.fullname} image={user.image} /> */}
        </span>

        <FiChevronDown className="hidden sm:block" size={12} />
      </Link>

      {/* Dropdown Start */}
      {dropdownOpen && (
        <div className="absolute right-0 mt-4 w-62.5 flex-col rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
          <ul className="flex flex-col gap-5 border-b border-stroke px-6 py-7.5 dark:border-strokedark">
            <li>
              <Link
                to="/profile"
                className="flex items-center gap-3.5 text-sm font-medium duration-300 ease-in-out hover:text-primary lg:text-base"
              >
                <FiUser className="text-current" size={22} />
                My Profile
              </Link>
            </li>
            <li>
              <Link
                to="#"
                className="flex items-center gap-3.5 text-sm font-medium duration-300 ease-in-out hover:text-primary lg:text-base"
              >
                <FiPhone className="text-current" size={22} />
                My Contacts
              </Link>
            </li>
            <li>
              <Link
                to="/settings"
                className="flex items-center gap-3.5 text-sm font-medium duration-300 ease-in-out hover:text-primary lg:text-base"
              >
                <FiSettings className="text-current" size={22} />
                Settings
              </Link>
            </li>
          </ul>
        </div>
      )}
    </div>
  );
};

export default DropdownUser;
