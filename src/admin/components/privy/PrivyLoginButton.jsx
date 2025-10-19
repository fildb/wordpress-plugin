import { Button } from "@/components/ui/button";
import { usePrivyAuth } from "@/admin/contexts/PrivyAuthContext";
import { Loader2, Wallet } from "lucide-react";

export function PrivyLoginButton({ className = "" }) {
  const { isPrivyReady, loginWithPrivy } = usePrivyAuth();

  return (
    <Button
      onClick={loginWithPrivy}
      disabled={!isPrivyReady}
      className={`bg-blue-600 hover:bg-blue-700 ${className}`}
    >
      {!isPrivyReady ? (
        <>
          <Loader2 className="mr-2 h-4 w-4 animate-spin" />
          Loading...
        </>
      ) : (
        <>
          <Wallet className="mr-2 h-4 w-4" />
          Login with Privy
        </>
      )}
    </Button>
  );
}
