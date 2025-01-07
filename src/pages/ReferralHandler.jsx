// ReferralHandler.jsx
import React, { useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';

const ReferralHandler = () => {
    const { wallet } = useParams();
    const navigate = useNavigate();

    useEffect(() => {
        if (wallet) {
            localStorage.setItem('referrer_wallet', wallet);
            navigate('/register');
        }
    }, [wallet, navigate]);

    return <div>Redirecting...</div>;
};

export default ReferralHandler;
