import { useState } from "react";
import { FaBell } from "react-icons/fa";
import ClickOutside from "../ClickOutside";
import { SiLivechat } from "react-icons/si";
import { IoMailOutline } from "react-icons/io5";
import { useAppContext } from "../context/AppContext";


const DropdownMessage = () => {
  const [dropdownOpen, setDropdownOpen] = useState(false);
  const [notifying, setNotifying] = useState(true);
  const {  unreadNotificationCount, setUnreadNotificationCount, unreadRequestCount, setUnreadRequestCount, unreadInboxCount, setUnreadInboxCount, user, setUserData } = useAppContext();

  return (
    <ClickOutside onClick={() => setDropdownOpen(false)} className="relative">
      <li className="relative">
        <a
          onClick={() => {
            setNotifying(false);
            setDropdownOpen(!dropdownOpen);
          }}
          className="relative flex h-8.5 w-8.5 items-center justify-center rounded-full border-[0.5px] border-stroke bg-gray hover:text-primary dark:border-strokedark dark:bg-meta-4 dark:text-white"
          href="#"
        >
          <span
            className={`absolute -right-0.5 -top-0.5 z-1 h-2 w-2 rounded-full bg-meta-1 ${
              notifying === false ? "hidden" : "inline"
            }`}
          >
            {unreadInboxCount}
            <span className="absolute -z-1 inline-flex h-full w-full animate-ping rounded-full bg-meta-1 opacity-75">{unreadInboxCount}</span>
          </span>
          {/* <FaBell className="fill-current text-current duration-300 ease-in-out" size={18} /> */}
          {/* <SiLivechat
           className="fill-current text-current duration-300 ease-in-out" 
           style={{ fontSize: '1.3em' }}  /> */}
            <IoMailOutline
           className="fill-current text-current duration-300 ease-in-out" 
           style={{ fontSize: '1.3em' }}  />
        </a>

        {/* <!-- Dropdown Start --> */}
        {dropdownOpen && (
          <div
            className={`absolute -right-16 mt-2.5 flex h-90 w-75 flex-col rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark sm:right-0 sm:w-80`}
          >
            <div className="px-4.5 py-3">
              <h5 className="text-sm font-medium text-bodydark2">Messages</h5>
            </div>

            <ul className="flex h-auto flex-col overflow-y-auto">
              <li>
                <a
                  className="flex gap-4.5 border-t border-stroke px-4.5 py-3 hover:bg-gray-2 dark:border-strokedark dark:hover:bg-meta-4"
                  href="/messages"
                >
                  <div className="h-12.5 w-12.5 rounded-full">
                    <img
                      width={112}
                      height={112}
                      src={"/images/user/user-02.png"}
                      alt="User"
                      style={{
                        width: "auto",
                        height: "auto",
                      }}
                    />
                  </div>

                  <div>
                    <h6 className="text-sm font-medium text-black dark:text-white">
                      Mariya Desoja
                    </h6>
                    <p className="text-sm">I like your confidence 💪</p>
                    <p className="text-xs">2min ago</p>
                  </div>
                </a>
              </li>
              {/* Add more list items similarly */}
            </ul>
          </div>
        )}
        {/* <!-- Dropdown End --> */}
      </li>
    </ClickOutside>
  );
};

export default DropdownMessage;
