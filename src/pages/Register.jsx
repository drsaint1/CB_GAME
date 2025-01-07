import React, { useState } from "react";
import { useParams, useNavigate } from 'react-router-dom';


function Register() {
    const [walletAddress, setWalletAddress] = useState("");
    const navigate = useNavigate();

    const handleRegister = async () => {
        const referrerWallet = localStorage.getItem('referrer_wallet');

        try {
            const response = await fetch("http://127.0.0.1:8000/api/register", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    wallet_address: walletAddress,
                    referrer_wallet: referrerWallet,
                }),
            });

            const data = await response.json();
            console.log(data);

            if(data.success == "success"){
                alert("Registration successful!"); // Provide feedback to the user
                // navigate('/');
            }   
        } catch (error) {
            console.error('Registration failed:', error);
        }
    };


    return (
        <div className="min-h-screen bg-gradient-to-b from-blue-900 to-gray-800 flex items-center justify-center font-montserrat">
            <div className="w-full max-w-md bg-white rounded-lg shadow-lg p-6">
                <h2 className="text-2xl font-bold text-center text-gray-800 mb-6">
                    Welcome to Bear Dodge Game!
                </h2>
                <p className="text-gray-600 text-center mb-6">
                    Enter your wallet address to register and start playing. If you were referred, you will automatically be connected!
                </p>
                <div className="space-y-4">
                    <input
                        type="text"
                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        placeholder="Enter Wallet Address"
                        value={walletAddress}
                        onChange={(e) => setWalletAddress(e.target.value)}
                    />
                    <button
                        onClick={handleRegister}
                        className="w-full px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
                    >
                        Register
                    </button>
                </div>
                <div className="mt-6 text-center">
                    <p className="text-gray-600">
                        Have questions?{" "}
                        <a
                            href="/support"
                            className="text-blue-600 hover:underline"
                        >
                            Contact Support
                        </a>
                    </p>
                </div>
            </div>
        </div>
    );
}

export default Register;
