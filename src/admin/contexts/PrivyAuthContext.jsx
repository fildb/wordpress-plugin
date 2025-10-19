import { createContext, useContext, useEffect, useState } from "react";
import { usePrivy } from "@privy-io/react-auth";
import { useSessionSigners } from "@privy-io/react-auth";

const PrivyAuthContext = createContext(null);

export function PrivyAuthProvider({ children }) {
  const { ready, authenticated, user, login, logout } = usePrivy();
  const { addSessionSigners } = useSessionSigners();
  const [sessionSignerAdded, setSessionSignerAdded] = useState(false);

  // Save Privy User ID to backend when authenticated
  useEffect(() => {
    if (authenticated && user?.id) {
      savePrivyUserToBackend(user.id);
    }
  }, [authenticated, user?.id]);

  // Add session signer to embedded wallet after authentication
  useEffect(() => {
    if (authenticated && user && !sessionSignerAdded) {
      addSessionSignerToWallet();
    }
  }, [authenticated, user, sessionSignerAdded]);

  const savePrivyUserToBackend = async (privyUserId) => {
    try {
      const response = await fetch(
        `${window.fidabrAdmin.apiUrl}llm/privy-auth`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": window.fidabrAdmin.nonce,
          },
          body: JSON.stringify({ privyUserId }),
        },
      );

      if (response.ok) {
        console.log("Privy authentication saved to backend");
      } else {
        console.error("Failed to save Privy authentication to backend");
      }
    } catch (error) {
      console.error("Error saving Privy authentication:", error);
    }
  };

  const deletePrivyUserFromBackend = async () => {
    try {
      const response = await fetch(
        `${window.fidabrAdmin.apiUrl}llm/privy-logout`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": window.fidabrAdmin.nonce,
          },
        },
      );

      if (response.ok) {
        console.log("Privy authentication cleared from backend");
      } else {
        console.error("Failed to clear Privy authentication from backend");
      }
    } catch (error) {
      console.error("Error clearing Privy authentication:", error);
    }
  };

  const addSessionSignerToWallet = async () => {
    try {
      // Get the user's embedded wallet address
      const embeddedWallet = user?.linkedAccounts?.find(
        (account) =>
          account.type === "wallet" && account.walletClientType === "privy",
      );

      if (!embeddedWallet) {
        console.log("No embedded wallet found for user");
        return;
      }

      const keyQuorumId = import.meta.env
        .VITE_PRIVY_SESSION_SIGNER_KEY_QUORUM_ID;

      if (!keyQuorumId) {
        console.warn(
          "Session signer key quorum ID not configured in .env file",
        );
        return;
      }

      // Add session signer to the user's embedded wallet
      await addSessionSigners({
        address: embeddedWallet.address,
        signers: [
          {
            signerId: keyQuorumId,
            // You can add policyIds here if you want to restrict what the session signer can do
            // policyIds: ['policy-id-1', 'policy-id-2']
          },
        ],
      });

      setSessionSignerAdded(true);
      console.log(
        "Session signer added successfully to wallet:",
        embeddedWallet.address,
      );
    } catch (error) {
      console.error("Error adding session signer to wallet:", error);
    }
  };

  const handleLogout = async () => {
    await deletePrivyUserFromBackend();
    setSessionSignerAdded(false);
    logout();
  };

  const value = {
    isPrivyReady: ready,
    isPrivyAuthenticated: authenticated,
    privyUser: user,
    loginWithPrivy: login,
    logoutFromPrivy: handleLogout,
    sessionSignerAdded,
  };

  return (
    <PrivyAuthContext.Provider value={value}>
      {children}
    </PrivyAuthContext.Provider>
  );
}

export function usePrivyAuth() {
  const context = useContext(PrivyAuthContext);
  if (!context) {
    throw new Error("usePrivyAuth must be used within a PrivyAuthProvider");
  }
  return context;
}
