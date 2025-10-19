import React from "react";
import ReactDOM from "react-dom/client";
import "./index.css";
import { RouterProvider } from "react-router-dom";
import { router } from "./routes";
import { ThemeProvider } from "@/components/theme-provider";
import { PrivyProvider } from "@privy-io/react-auth";
import { PrivyAuthProvider } from "@/admin/contexts/PrivyAuthContext";
import { filecoinCalibration } from "viem/chains";

const el = document.getElementById("fidabr-admin-app");

if (el) {
  ReactDOM.createRoot(el).render(
    <PrivyProvider
      appId={import.meta.env.VITE_PRIVY_APP_ID}
      config={{
        appearance: {
          theme: "light",
          accentColor: "#2563eb",
          logo: undefined,
        },
        loginMethods: ["wallet", "email", "sms", "google", "twitter"],
        embeddedWallets: {
          ethereum: {
            createOnLogin: "all-users",
          },
        },
        defaultChain: filecoinCalibration,
        supportedChains: [filecoinCalibration],
      }}>
      <PrivyAuthProvider>
        <ThemeProvider defaultTheme="light" storageKey="fidabr-admin-theme">
          <RouterProvider router={router} />
        </ThemeProvider>
      </PrivyAuthProvider>
    </PrivyProvider>,
  );
}
