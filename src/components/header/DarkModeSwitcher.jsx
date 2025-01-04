// import useColorMode from "@/hooks/useColorMode";
// const [colorMode, setColorMode] = useColorMode();
import useColorMode from "../../hooks/useColorMode";

import { useState,useEffect } from "react";
import { FaSun, FaMoon } from "react-icons/fa";

const DarkModeSwitcher = () => {
  // Assuming useColorMode hook exists to handle light/dark mode toggle
  // const [colorMode, setColorMode] = useState("light"); // Use your existing useColorMode hook here
  const [colorMode, setColorMode] = useColorMode();
  const [isMounted, setIsMounted] = useState(false); 

  useEffect(() => {
    setIsMounted(true); // Set to true after the component is mounted
  }, []);

  return (
    <li>
      <label
        className={`relative m-0 block h-7.5 w-14 rounded-full ${
          colorMode === "dark" ? "bg-primary" : "bg-stroke"
        }`}
      >
        <input
          type="checkbox"
          onChange={() => {
            setColorMode(colorMode === "light" ? "dark" : "light");
          }}
          className="absolute top-0 z-50 m-0 h-full w-full cursor-pointer opacity-0"
        />
        <span
          className={`absolute left-[3px] top-1/2 flex h-6 w-6 -translate-y-1/2 translate-x-0 items-center justify-center rounded-full bg-white shadow-switcher duration-75 ease-linear ${
            colorMode === "dark" && "!right-[3px] !translate-x-full"
          }`}
        >
         {isMounted ? (
            colorMode === "light" ? (
              <FaSun size={16} color="#969AA1" />
            ) : (
              <FaMoon size={16} color="#969AA1" />
            )
          ) : null}
        </span>
      </label>
    </li>
  );
};

export default DarkModeSwitcher;
