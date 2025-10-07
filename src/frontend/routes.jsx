import { createHashRouter } from "react-router-dom";

import ErrorPage from "@/frontend/pages/error/Error";

const Home = () => {
  return (
    <div className="flex min-h-screen items-center justify-center">
      <div className="text-center">
        <h1 className="text-2xl font-bold">FiloDataBroker Plugin</h1>
        <p className="mt-4 text-gray-600">
          This plugin is configured via the WordPress admin panel.
        </p>
      </div>
    </div>
  );
};

export const router = createHashRouter([
  {
    path: "/",
    element: <Home />,
    errorElement: <ErrorPage />,
  },
]);
