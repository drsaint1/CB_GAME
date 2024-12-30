import React, { useMemo, useEffect } from "react";
import {
  ConnectionProvider,
  WalletProvider,
  useWallet,
} from "@solana/wallet-adapter-react";
import {
  PhantomWalletAdapter,
  SolflareWalletAdapter,
} from "@solana/wallet-adapter-wallets";
import {
  WalletModalProvider,
  WalletMultiButton,
} from "@solana/wallet-adapter-react-ui";
import "@solana/wallet-adapter-react-ui/styles.css";

function WalletIntegration({ onConnect }) {
  const wallets = useMemo(
    () => [new PhantomWalletAdapter(), new SolflareWalletAdapter()],
    []
  );

  const WalletConnectionChecker = () => {
    const { connected } = useWallet();

    useEffect(() => {
      if (connected) {
        onConnect(); // Notify parent when the wallet is connected
      }
    }, [connected]);

    return null; // No visual output, only logic
  };

  return (
    <ConnectionProvider endpoint="https://api.devnet.solana.com">
      <WalletProvider wallets={wallets}>
        {/* Removed autoConnect */}
        <WalletModalProvider>
          <WalletMultiButton />
          <WalletConnectionChecker />
        </WalletModalProvider>
      </WalletProvider>
    </ConnectionProvider>
  );
}

export default WalletIntegration;
