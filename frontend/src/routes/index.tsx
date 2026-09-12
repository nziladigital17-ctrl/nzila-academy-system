import { createBrowserRouter, RouterProvider } from 'react-router-dom';
import { AppLayout } from '@/layouts/AppLayout';
import { GuestLayout } from '@/layouts/GuestLayout';
import { Login } from '@/pages/auth/Login';
import { Dashboard } from '@/pages/Dashboard';
import { NotFound, Forbidden, ServerError } from '@/pages/errors/Errors';
import { ProtectedRoute } from '@/components/ProtectedRoute';

const router = createBrowserRouter([
  {
    path: '/',
    element: <ProtectedRoute />,
    children: [
      {
        element: <AppLayout />,
        children: [
          {
            index: true,
            element: <Dashboard />,
          },
          // Future protected routes go here
        ],
      },
    ],
  },
  {
    element: <GuestLayout />,
    children: [
      {
        path: '/login',
        element: <Login />,
      },
    ],
  },
  {
    path: '/403',
    element: <Forbidden />,
  },
  {
    path: '/500',
    element: <ServerError />,
  },
  {
    path: '*',
    element: <NotFound />,
  },
]);

export function AppRouter() {
  return <RouterProvider router={router} />;
}
