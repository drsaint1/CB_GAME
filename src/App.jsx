// import React from "react";
// import { BrowserRouter as Router, Routes, Route } from "react-router-dom";
// import Register from "./pages/Register";
// import Home from "./pages/Home";
// import GamePlay from "./pages/Gameplay";
// import ReferralHandler from "./pages/ReferralHandler";
// import DefaultLayout from "./components/layouts/DefaultLayout";
// import { BrowserRouter } from "react-router-dom";


// function App() {
//     return (

//         <DefaultLayout>
//         {/* <Router> */}
//             <Routes>
//                 <Route path="/" element={<GamePlay />} />
//                 <Route path="/referrer" element={<Home />} />
//                 <Route path="/register" element={<Register />} />
//                 <Route path="/referral/:wallet" element={<ReferralHandler />} />
//                 <Route
//                     path="*"
//                     element={
//                         <div className="min-h-screen flex items-center justify-center">
//                             <h1 className="text-3xl font-bold text-gray-800">
//                                 404 - Page Not Found
//                             </h1>
//                         </div>
//                     }
//                 />
//             </Routes>
//         {/* </Router> */}
//         </DefaultLayout>
//     );
// }

// export default App;


import { useEffect, useState } from 'react';
import { Route, Routes, useLocation } from 'react-router-dom';

// import Loader from './common/Loader';
import Loader from './common/Loader';
import PageTitle from './components/PageTitle';
import DefaultLayout from './layouts/DefaultLayout';
import Register from "./pages/Register";
import Home from "./pages/Home";
import GamePlay from "./pages/Gameplay";
import ReferralHandler from "./pages/ReferralHandler";

function App() {
  const [loading, setLoading] = useState(true);
  const { pathname } = useLocation();

  useEffect(() => {
    window.scrollTo(0, 0);
  }, [pathname]);

  useEffect(() => {
    setTimeout(() => setLoading(false), 1000);
  }, []);

  return loading ? (
    <Loader />
  ) : (
    <DefaultLayout>
      <Routes>
        <Route
          index
          element={
            <>
              <PageTitle title="Play Game" />
              <GamePlay />
            </>
          }
        />
        <Route
          path="/referral/:wallet"
          element={
            <>
              <PageTitle title="CB GAME | REFERRAL LINK" />
              <ReferralHandler />
            </>
          }
        />

        <Route
          path="*"
          element={
            <div className="min-h-screen flex items-center justify-center">
              <h1 className="text-3xl font-bold text-gray-800">
                404 - Page Not Found
              </h1>
            </div>
          }
        />

        <Route
          path="/register"
          element={
            <>
              <PageTitle title="Register | Enter your wallet " />
              <Register />
            </>
          }
        />
        <Route
          path="/home"
          element={
            <>
              <PageTitle title="Home | Go home" />
              <Home />
            </>
          }
        />
      </Routes>
    </DefaultLayout>
  );
}

export default App;
