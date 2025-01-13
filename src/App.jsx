import { useEffect, useState } from 'react';
import { Route, Routes, useLocation } from 'react-router-dom';

import Loader from './common/Loader';
import PageTitle from './components/PageTitle';
import DefaultLayout from './layouts/DefaultLayout';
import Register from "./pages/Register";
import Home from "./pages/Home";
import GamePlay from "./pages/Gameplay";
import ReferralHandler from "./pages/ReferralHandler";
import LeaderboardPage from './pages/LeaderBoard';
import ReferralPage from './pages/Referrals';
import MobileBottomNav from './layouts/MobileBottomNav';
import WalletPage from './pages/Wallet';

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
    <Routes>
      {/* Routes Without DefaultLayout */}
      <Route
        path="/"
        element={
          <>
            <PageTitle title="Home | Go home" />
            <Home />
          </>
        }
      />

      <Route
        path="/connect"
        element={
          <>
            <PageTitle title="Home | Go home" />
            <Home />
          </>
        }
      />

      {/* Routes With DefaultLayout */}
      <Route
        path="/play"
        element={
          <DefaultLayout>
            <PageTitle title="Play Game" />
            <GamePlay />
            <MobileBottomNav />
          </DefaultLayout>
        }
      />
      <Route
        path="/referral/:wallet"
        element={
          <DefaultLayout>
            <PageTitle title="CB GAME | REFERRAL LINK" />
            <ReferralHandler />
          </DefaultLayout>
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
        path="/leaderboard"
        element={
          <DefaultLayout>
            <PageTitle title="Leaderboard | Check who is topping" />
            <LeaderboardPage />
            <MobileBottomNav />
          </DefaultLayout>
        }
      />

      <Route
        path="/referrals"
        element={
          <DefaultLayout>
            <PageTitle title="Referrals | Your referrals" />
            <ReferralPage />
            <MobileBottomNav />
          </DefaultLayout>
        }
      />

<Route
        path="/wallet"
        element={
          <DefaultLayout>
            <PageTitle title="Wallet | Your Wallet" />
            <WalletPage/>
            <MobileBottomNav />
          </DefaultLayout>
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
    </Routes>
  );
}

export default App;
