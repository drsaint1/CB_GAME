import { useState, useCallback, useEffect } from "react";
import * as anchor from "@coral-xyz/anchor";
import {
  Connection,
  Keypair,
  PublicKey,
  SystemProgram,
  Transaction,
  ComputeBudgetProgram,
} from "@solana/web3.js";
import {
  getAssociatedTokenAddress,
  createAssociatedTokenAccountInstruction,
  getMint,
} from "@solana/spl-token";
import { useWallet, useAnchorWallet } from "@solana/wallet-adapter-react";
import idl from "../idl.json";
import {
  SOLANA_NETWORK,
  CB_TOKEN_MINT,
  TOKEN_PROGRAM_ID,
  PROGRAM_ADDRESS,
} from "../constants";
import axios from "axios";

// Ensure Buffer is available in the browser
if (typeof Buffer === "undefined") {
  globalThis.Buffer = require("buffer").Buffer;
}

export const useCreateTransaction = () => {
  const [errorMessage, setErrorMessage] = useState("");
  const [successMessage, setSuccessMessage] = useState("");

  const createTransaction = async (action, amount) => {
    const walletData = JSON.parse(localStorage.getItem("walletData"));

    if (!walletData?.walletAddress) {
      setErrorMessage("Wallet address not found.");
      return;
    }

    try {
      setErrorMessage("");
      setSuccessMessage("");

      const response = await fetch(`${import.meta.env.VITE_API_URL}/transactions/create`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(
          {
            user_id: walletData.walletAddress,
            wallet_address: walletData.walletAddress,
            action,
            amount,
          }
        ),
      });

      setSuccessMessage(
        `✅ Transaction Successful`
      );

    } catch (error) {
      console.error(error);
      setErrorMessage(
        error.response?.data?.message || "Failed to create transaction"
      );
    }
  };

  return { createTransaction, errorMessage, successMessage };
};

export function initializeProgram(wallet) {
  // if (!wallet?.publicKey) {
  //   console.error("Wallet is null or undefined");
  //   return null;
  // }

  const connection = new Connection(SOLANA_NETWORK, {
    commitment: "confirmed",
  });

  const provider = new anchor.AnchorProvider(connection, wallet, {
    commitment: "confirmed",
    preflightCommitment: "confirmed",
  });

  return new anchor.Program(idl, provider);
}

export function useInitializeSession(program) {
  const wallet = useAnchorWallet();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const initializeSession = useCallback(
    async (referrer = null) => {
      setLoading(true);
      setError(null);

      try {
        if (!program || !wallet?.publicKey) {
          throw new Error("Wallet not connected or program not initialized");
        }

        // Check if session PDA is already stored in local storage
        const storedSessionPDA = localStorage.getItem("sessionPDA");
        if (storedSessionPDA) {
          console.log("Session PDA already exists in local storage.");
          return new PublicKey(storedSessionPDA); // Return the stored PDA
        }

        // Generate keypair for session_data
        const sessionDataKeypair = Keypair.generate();
        const sessionPDA = sessionDataKeypair.publicKey;

        // Store the session PDA in local storage
        localStorage.setItem("sessionPDA", sessionPDA.toBase58());

        // Convert referrer to PublicKey if provided
        const referrerPublicKey = referrer ? new PublicKey(referrer) : null;

        // Fetch the latest blockhash
        const { blockhash } =
          await program.provider.connection.getLatestBlockhash("confirmed");

        // Build transaction
        const tx = new Transaction({
          feePayer: wallet.publicKey,
          recentBlockhash: blockhash,
        });

        // Add initialize_session instruction
        tx.add(
          await program.methods
            .initializeSession(referrerPublicKey)
            .accounts({
              sessionData: sessionPDA,
              player: wallet.publicKey,
              systemProgram: SystemProgram.programId,
            })
            .instruction()
        );

        // Partially sign with sessionDataKeypair
        tx.partialSign(sessionDataKeypair);

        // Sign the transaction with the wallet
        const signedTx = await wallet.signTransaction(tx);

        // Send transaction and confirm
        const txId = await program.provider.connection.sendRawTransaction(
          signedTx.serialize(),
          {
            skipPreflight: false,
            commitment: "confirmed",
          }
        );

        // Confirm transaction
        const confirmation =
          await program.provider.connection.confirmTransaction(
            txId,
            "confirmed"
          );

        if (confirmation.value.err) {
          console.error("Transaction failed:", confirmation.value.err);
          throw new Error(
            `Transaction failed: ${JSON.stringify(confirmation.value.err)}`
          );
        }

        console.log("Session initialized successfully:", confirmation);
        return sessionPDA;
      } catch (err) {
        console.error("Initialize session error:", err);
        setError(err.message || "Failed to initialize session");
      } finally {
        setLoading(false);
      }
    },
    [program, wallet]
  );

  return { initializeSession, loading, error };
}

export function useInitializeConfig(program) {
  const wallet = useAnchorWallet();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const initializeConfig = useCallback(
    async (shieldPrice, referralReward, continuePrice) => {
      setLoading(true);
      setError(null);

      try {
        if (!program || !wallet || !wallet.publicKey) {
          throw new Error("Wallet not connected or Program not initialized");
        }

        // Check if config PDA exists in local storage
        const storedConfigPDA = localStorage.getItem("configPDA");
        if (storedConfigPDA) {
          console.log("Config PDA already exists in local storage.");
          return new PublicKey(storedConfigPDA); // Return the stored PDA
        }

        // Generate a keypair for the config account
        const configKeypair = Keypair.generate();

        // Create a new transaction
        const transaction = new Transaction();

        // Add the initialize_config instruction to the transaction
        transaction.add(
          await program.methods
            .initializeConfig(
              wallet.publicKey, // Admin public key
              new anchor.BN(shieldPrice),
              new anchor.BN(referralReward),
              new anchor.BN(continuePrice)
            )
            .accounts({
              config: configKeypair.publicKey,
              payer: wallet.publicKey,
              systemProgram: SystemProgram.programId,
            })
            .instruction()
        );

        transaction.recentBlockhash = (
          await program.provider.connection.getLatestBlockhash()
        ).blockhash;
        transaction.feePayer = wallet.publicKey;

        transaction.partialSign(configKeypair);

        const signedTransaction = await wallet.signTransaction(transaction);

        const signature = await program.provider.connection.sendRawTransaction(
          signedTransaction.serialize(),
          {
            skipPreflight: false,
            preflightCommitment: "confirmed",
          }
        );

        // Confirm the transaction
        await program.provider.connection.confirmTransaction(
          signature,
          "confirmed"
        );

        // Store config PDA in local storage
        localStorage.setItem("configPDA", configKeypair.publicKey.toBase58());

        console.log("Config initialized. Transaction ID:", signature);
        return configKeypair.publicKey;
      } catch (err) {
        console.error("Failed to initialize config:", err);
        setError(err.message || "Failed to initialize config");
      } finally {
        setLoading(false);
      }
    },
    [program, wallet]
  );

  return { initializeConfig, loading, error };
}

const REQUIRED_CB_AMOUNT = 100;

export function usePurchaseShield(program) {
  const wallet = useAnchorWallet();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const purchaseShield = useCallback(
    async (sessionPDA, configPDA) => {
      setLoading(true);
      setError(null);

      try {
        if (!program || !wallet?.publicKey) {
          throw new Error("Wallet not connected or Program not initialized");
        }

        if (!CB_TOKEN_MINT) {
          throw new Error(
            "CB_TOKEN_MINT is not defined in environment variables"
          );
        }

        // Ensure Token Mint is Valid and Exists
        let tokenMintInfo;
        try {
          tokenMintInfo = await getMint(
            program.provider.connection,
            new PublicKey(CB_TOKEN_MINT)
          );
          if (!tokenMintInfo) {
            throw new Error(`Token Mint ${CB_TOKEN_MINT} not found`);
          }
        } catch (mintError) {
          console.error("Failed to get mint info:", mintError);
          setError("Invalid Token Mint");
          return;
        }

        // Get player ATA (Associated Token Account)
        const playerATA = await getAssociatedTokenAddress(
          new PublicKey(CB_TOKEN_MINT),
          wallet.publicKey
        );

        // Check if the player's ATA exists; if not, create it
        const ataInfo = await program.provider.connection.getAccountInfo(
          playerATA
        );
        if (!ataInfo) {
          console.warn("Player ATA does not exist, creating...");
          const createAtaTx = new Transaction().add(
            createAssociatedTokenAccountInstruction(
              wallet.publicKey,
              playerATA,
              wallet.publicKey,
              new PublicKey(CB_TOKEN_MINT)
            )
          );

          const { blockhash } =
            await program.provider.connection.getLatestBlockhash("confirmed");
          createAtaTx.recentBlockhash = blockhash;
          createAtaTx.feePayer = wallet.publicKey;

          const signedCreateAtaTx = await wallet.signTransaction(createAtaTx);
          const ataTxId = await program.provider.connection.sendRawTransaction(
            signedCreateAtaTx.serialize(),
            { skipPreflight: true, commitment: "confirmed" }
          );

          await program.provider.connection.confirmTransaction(
            ataTxId,
            "confirmed"
          );
        }

        // Check if the player has enough tokens in their ATA
        const playerTokenBalance =
          await program.provider.connection.getTokenAccountBalance(playerATA);
        if (playerTokenBalance.value.uiAmount < REQUIRED_CB_AMOUNT) {
          console.error("Insufficient balance to purchase shield");
          setError("Insufficient balance to purchase shield");
          return;
        }

        // Derive PDAs (Program Derived Addresses)
        const [treasuryPDA] = PublicKey.findProgramAddressSync(
          [Buffer.from("treasury-token-account")],
          program.programId
        );

        // Ensure Config and Treasury Accounts Exist
        const configAccountInfo =
          await program.provider.connection.getAccountInfo(configPDA);
        if (!configAccountInfo) {
          console.error("Config Account Not Initialized");
          setError("Config Account Not Initialized");
          return;
        }

        const treasuryAccountInfo =
          await program.provider.connection.getAccountInfo(treasuryPDA);
        if (!treasuryAccountInfo) {
          console.error("Treasury Account Not Initialized");
          setError("Treasury Account Not Initialized");
          return;
        }

        // Build the transaction to purchase a shield
        const { blockhash } =
          await program.provider.connection.getLatestBlockhash("confirmed");

        const tx = new Transaction({
          feePayer: wallet.publicKey,
          recentBlockhash: blockhash,
        }).add(
          ComputeBudgetProgram.setComputeUnitPrice({ microLamports: 100_000 }), // Priority fee
          ComputeBudgetProgram.setComputeUnitLimit({ units: 1_400_000 }), // Max compute units
          await program.methods
            .purchaseShield()
            .accounts({
              playerTokenAccount: playerATA,
              treasuryAccount: treasuryPDA,
              sessionData: sessionPDA,
              config: configPDA,
              player: wallet.publicKey,
              tokenProgram: TOKEN_PROGRAM_ID,
              systemProgram: SystemProgram.programId,
            })
            .instruction()
        );

        // Sign and send the transaction
        const signedTx = await wallet.signTransaction(tx);
        const txId = await program.provider.connection.sendRawTransaction(
          signedTx.serialize(),
          { skipPreflight: true, commitment: "confirmed" }
        );

        // Confirm transaction
        const confirmation =
          await program.provider.connection.confirmTransaction(
            txId,
            "confirmed"
          );

        console.log(confirmation, "confirmed");
        if (confirmation.value.err) {
          console.error("Transaction failed:", confirmation.value.err);
          throw new Error(
            `Transaction failed: ${JSON.stringify(confirmation.value.err)}`
          );
        }
        return txId;
      } catch (err) {
        console.error("Purchase Shield Error:", err);
        setError(err.message || "Failed to purchase shield");
      } finally {
        setLoading(false);
      }
    },
    [program, wallet]
  );

  return { purchaseShield, loading, error };
}

// Withdraw tokens hook
export function useWithdrawTokens(program) {
  const wallet = useAnchorWallet();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const withdrawTokens = useCallback(
    async (sessionPDA, amount) => {
      setLoading(true);
      setError(null);

      try {
        if (!program || !wallet?.publicKey) {
          throw new Error("Wallet not connected or Program not initialized");
        }

        if (!(sessionPDA instanceof PublicKey)) {
          sessionPDA = new PublicKey(sessionPDA); // Ensure it's a PublicKey
        }

        // Get player ATA (Associated Token Account)
        const playerATA = await getAssociatedTokenAddress(
          new PublicKey(CB_TOKEN_MINT),
          wallet.publicKey
        );

        // Derive vault PDA
        const [vaultPDA] = PublicKey.findProgramAddressSync(
          [Buffer.from("vault-token-account")],
          program.programId
        );

        // Ensure vault account exists
        const vaultAccountInfo =
          await program.provider.connection.getAccountInfo(vaultPDA);
        if (!vaultAccountInfo) {
          console.error("Vault account not initialized.");
          setError("Vault account not initialized.");
          return;
        }

        // Ensure session account exists
        const sessionAccountInfo =
          await program.provider.connection.getAccountInfo(sessionPDA);
        if (!sessionAccountInfo) {
          console.error("Session account not initialized.");
          setError("Session account not initialized.");
          return;
        }

        // Fetch recent blockhash
        const { blockhash } =
          await program.provider.connection.getLatestBlockhash("confirmed");

        // Build the transaction to withdraw tokens
        const tx = new Transaction({
          feePayer: wallet.publicKey,
          recentBlockhash: blockhash,
        }).add(
          await program.methods
            .withdrawTokens(new anchor.BN(amount))
            .accounts({
              vaultAccount: vaultPDA,
              playerTokenAccount: playerATA,
              sessionData: sessionPDA,
              tokenProgram: TOKEN_PROGRAM_ID,
              player: wallet.publicKey,
            })
            .instruction()
        );

        // Sign and send the transaction
        const signedTx = await wallet.signTransaction(tx);
        const txId = await program.provider.connection.sendRawTransaction(
          signedTx.serialize(),
          { skipPreflight: false, commitment: "confirmed" }
        );

        // Confirm transaction
        await program.provider.connection.confirmTransaction(txId, "confirmed");

        console.log("Tokens withdrawn successfully. Transaction ID:", txId);
      } catch (err) {
        console.error("Withdraw tokens error:", err);
        setError(err.message || "Failed to withdraw tokens");
      } finally {
        setLoading(false);
      }
    },
    [program, wallet]
  );

  return { withdrawTokens, loading, error };
}

export function useContinueGame(program) {
  const wallet = useAnchorWallet();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const continueGame = useCallback(
    async (sessionPDA, configPDA) => {
      setLoading(true);
      setError(null);

      try {
        if (!program || !wallet?.publicKey) {
          throw new Error("Wallet not connected or Program not initialized");
        }

        // Derive PDAs (Program Derived Addresses)
        const [treasuryPDA] = PublicKey.findProgramAddressSync(
          [Buffer.from("treasury-token-account")],
          program.programId
        );

        // Ensure Config and Treasury Accounts Exist
        const configAccountInfo =
          await program.provider.connection.getAccountInfo(configPDA);
        if (!configAccountInfo) {
          console.error("Config Account Not Initialized");
          setError("Config Account Not Initialized");
          return;
        }

        const treasuryAccountInfo =
          await program.provider.connection.getAccountInfo(treasuryPDA);
        if (!treasuryAccountInfo) {
          console.error("Treasury Account Not Initialized");
          setError("Treasury Account Not Initialized");
          return;
        }

        // Get player ATA (Associated Token Account)
        const playerATA = await getAssociatedTokenAddress(
          new PublicKey(CB_TOKEN_MINT),
          wallet.publicKey
        );

        // Check if the player's ATA exists; if not, create it
        const ataInfo = await program.provider.connection.getAccountInfo(
          playerATA
        );
        if (!ataInfo) {
          console.warn("Player ATA does not exist, creating...");
          const createAtaTx = new Transaction().add(
            createAssociatedTokenAccountInstruction(
              wallet.publicKey,
              playerATA,
              wallet.publicKey,
              new PublicKey(CB_TOKEN_MINT)
            )
          );

          const { blockhash } =
            await program.provider.connection.getLatestBlockhash("confirmed");
          createAtaTx.recentBlockhash = blockhash;
          createAtaTx.feePayer = wallet.publicKey;

          const signedCreateAtaTx = await wallet.signTransaction(createAtaTx);
          const ataTxId = await program.provider.connection.sendRawTransaction(
            signedCreateAtaTx.serialize(),
            { skipPreflight: true, commitment: "confirmed" }
          );

          await program.provider.connection.confirmTransaction(
            ataTxId,
            "confirmed"
          );
        }

        // Build the transaction to continue the game
        const { blockhash } =
          await program.provider.connection.getLatestBlockhash("confirmed");

        const tx = new Transaction({
          feePayer: wallet.publicKey,
          recentBlockhash: blockhash,
        }).add(
          ComputeBudgetProgram.setComputeUnitPrice({ microLamports: 100_000 }), // Priority fee
          ComputeBudgetProgram.setComputeUnitLimit({ units: 1_400_000 }), // Max compute units
          await program.methods
            .continueGame()
            .accounts({
              playerTokenAccount: playerATA, // Include playerTokenAccount
              sessionData: sessionPDA,
              config: configPDA,
              treasuryAccount: treasuryPDA,
              player: wallet.publicKey,
              tokenProgram: TOKEN_PROGRAM_ID,
              systemProgram: SystemProgram.programId,
            })
            .instruction()
        );

        // Sign and send the transaction
        const signedTx = await wallet.signTransaction(tx);
        const txId = await program.provider.connection.sendRawTransaction(
          signedTx.serialize(),
          { skipPreflight: true, commitment: "confirmed" }
        );

        // Confirm transaction
        const confirmation =
          await program.provider.connection.confirmTransaction(
            txId,
            "confirmed"
          );

        if (confirmation.value.err) {
          console.error("Transaction failed:", confirmation.value.err);
          throw new Error(
            `Transaction failed: ${JSON.stringify(confirmation.value.err)}`
          );
        }

        return txId;
      } catch (err) {
        console.error("Continue Game Error:", err);
        setError(err.message || "Failed to continue game");
      } finally {
        setLoading(false);
      }
    },
    [program, wallet]
  );

  return { continueGame, loading, error };
}

export function useSavePoints(program) {
  const wallet = useAnchorWallet();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const savePoints = useCallback(
    async (sessionPDA, amount) => {
      setLoading(true);
      setError(null);

      try {
        if (!program || !wallet?.publicKey) {
          throw new Error("Wallet not connected or Program not initialized");
        }
        // Fetch recent blockhash
        const { blockhash } =
          await program.provider.connection.getLatestBlockhash("confirmed");
        const tx = new Transaction({
          feePayer: wallet.publicKey,
          recentBlockhash: blockhash,
        }).add(
          await program.methods
            .updateEarned(new anchor.BN(amount))
            .accounts({
              sessionData: sessionPDA,
              player: wallet.publicKey,
            })
            .instruction()
        );
        // Sign and send the transaction
        const signedTx = await wallet.signTransaction(tx);
        const txId = await program.provider.connection.sendRawTransaction(
          signedTx.serialize(),
          { skipPreflight: false, commitment: "confirmed" }
        );

        // Confirm transaction
        await program.provider.connection.confirmTransaction(txId, "confirmed");

        console.log("Earned tokens updated successfully.");
      } catch (err) {
        console.error("Failed to update earned tokens:", err);
        setError(err.message || "Failed to update earned tokens");
      } finally {
        setLoading(false);
      }
    },
    [program, wallet]
  );

  return { savePoints, loading, error };
}

export function useWalletData(userPublicKey) {
  const [balance, setBalance] = useState(0);
  const [transactions, setTransactions] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const fetchWalletData = useCallback(
    async (program) => {
      try {
        setLoading(true);
        setError("");
        const connection = new Connection(SOLANA_NETWORK, "confirmed");
        // Get the associated token account for CB_TOKEN_MINT
        const tokenAccount = await getAssociatedTokenAddress(
          new PublicKey(CB_TOKEN_MINT),
          userPublicKey
        );
        const balanceInfo = await connection.getTokenAccountBalance(
          tokenAccount
        );
        setBalance(balanceInfo.value.uiAmount ?? 0);

        // If your on-chain program stores transactions in sessionData accounts:
        if (program) {
          const txns = await program.account.sessionData.all([
            {
              memcmp: {
                offset: 8, // adjust offset based on your account structure
                bytes: userPublicKey.toBase58(),
              },
            },
          ]);
          const mappedTxns = txns.map((txn) => {
            const account = txn.account || {};
            const earnedCb = account.earnedCb || 0;
            return {
              date: new Date().toLocaleDateString(),
              description: "Earned CB",
              amount: earnedCb,
              status: "Success",
            };
          });
          setTransactions(mappedTxns);
        }
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    },
    [userPublicKey]
  );

  return { balance, transactions, loading, error, fetchWalletData };
}

export function useCheckBalance(program) {
  const wallet = useAnchorWallet();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [balance, setBalance] = useState(null);

  const checkBalance = useCallback(
    async (sessionPDA) => {
      setLoading(true);
      setError(null);
      setBalance(null);

      try {
        if (!program || !wallet?.publicKey) {
          throw new Error("Wallet not connected or Program not initialized");
        }

        const sessionDataAccount = await program.account.sessionData.fetch(
          sessionPDA
        );

        setBalance(sessionDataAccount.earnedCb.toNumber());
        console.log(
          "Balance checked successfully:",
          sessionDataAccount.earnedCb.toNumber()
        );
      } catch (err) {
        console.error("Failed to check balance:", err);
        setError(err.message || "Failed to check balance");
      } finally {
        setLoading(false);
      }
    },
    [program, wallet]
  );

  return { checkBalance, loading, error, balance };
}
