import { FaComments, FaUsers, FaBolt, FaUserCircle } from 'react-icons/fa';
import { BiPowerOff } from 'react-icons/bi';
import { IoPowerOutline } from 'react-icons/io5';
import { TbBrandGoogleAnalytics } from "react-icons/tb";
import { BiConversation } from 'react-icons/bi';
import { MdWidgets } from 'react-icons/md';
import { RiNotification2Line, RiNotification2Fill, RiRobot2Fill,RiRobot2Line, RiRobot3Fill, RiRobot3Line,RiHome7Line, RiHome7Fill, RiAccountBoxFill,RiAccountBoxLine,RiLogoutCircleLine } from "react-icons/ri";
import { BiExtension } from 'react-icons/bi';
import { Link } from '@remix-run/react';

const MobileBottomNav = () => {
  return (
    <div className="fixed bottom-0 inset-x-0 z-50 bg-white shadow-lg border-t border-stroke lg:hidden dark:bg-black dark:border-black dark:drop-shadow-none ">
      <div className="flex justify-around items-center h-16 font-medium text-gray">
        <Link to="/Play" className="flex flex-col items-center">
          <BiConversation size={24} className="text-black dark:text-[#ccc]" />
          <span className="text-sm text-black dark:text-[#ccc]">Play Game</span>
        </Link>
        <Link to="/leaderboard" className="flex flex-col items-center">
          <BiExtension size={24} className="text-black dark:text-[#ccc]" />
          <span className="text-sm text-black dark:text-[#ccc]">Leaderboards</span>
        </Link>
        <Link to="/leaderboard" className="flex flex-col items-center">
          {/* <FaBolt size={24} className="text-black dark:text-[#ccc]" /> */}
          <IoPowerOutline size={24} className="text-black dark:text-[#ccc]"/>
          <span className="text-sm text-black dark:text-[#ccc]">Refferals</span>
        </Link>
        
        <Link to="/wallet" className="flex flex-col items-center">
          <RiRobot3Line size={24} className="text-black dark:text-[#ccc]" />
          <span className="text-sm text-black dark:text-[#ccc]">Wallet</span>
        </Link>
      
        {/* <Link to="/" className="flex flex-col items-center">
          <TbBrandGoogleAnalytics size={24} className="text-black dark:text-[#ccc]" />
          <span className="text-sm text-black dark:text-[#ccc]">Analytics</span>
        </Link> */}
      </div>
    </div>
  );
};

export default MobileBottomNav;
