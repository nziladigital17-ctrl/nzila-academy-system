import { createBrowserRouter, RouterProvider } from 'react-router-dom';
import { AppLayout } from '@/layouts/AppLayout';
import { GuestLayout } from '@/layouts/GuestLayout';
import { Login } from '@/pages/auth/Login';
import { Dashboard } from '@/pages/Dashboard';
import { AcademicStructurePage } from '@/pages/academic/AcademicStructurePage';
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
          {
            path: 'academic',
            element: <ProtectedRoute requiredPermission="academic_years.view" />,
            children: [
              {
                index: true,
                element: <AcademicStructurePage />,
              },
            ],
          },
          {
            path: 'students',
            element: <ProtectedRoute requiredPermission="students.view" />,
            children: [
              {
                index: true,
                lazy: async () => {
                  const { StudentList } = await import('@/pages/students/StudentList');
                  return { Component: StudentList };
                },
              },
              {
                path: ':id',
                lazy: async () => {
                  const { StudentDetail } = await import('@/pages/students/StudentDetail');
                  return { Component: StudentDetail };
                },
              },
            ],
          },
          {
            path: 'enrollments',
            element: <ProtectedRoute requiredPermission="enrollments.view" />,
            children: [
              {
                index: true,
                lazy: async () => {
                  const { EnrollmentList } = await import('@/pages/enrollments/EnrollmentList');
                  return { Component: EnrollmentList };
                },
              },
            ],
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
