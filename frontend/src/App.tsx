import { useEffect } from 'react'
import { BrowserRouter, Routes, Route } from 'react-router-dom'
import { AuthProvider } from './contexts/AuthContext'
import PrivateRoute from './components/PrivateRoute'
import ProtectedRoute from './components/ProtectedRoute'
import Layout from './components/Layout'
import PushNotificationManager from './components/PushNotificationManager'
import Dashboard from './pages/Dashboard'
import Clients from './pages/Clients'
import Leads from './pages/Leads'
import Orders from './pages/Orders'
import Invoices from './pages/Invoices'
import Deliveries from './pages/Deliveries'
import Payments from './pages/Payments'
import Tasks from './pages/Tasks'
import Contracts from './pages/Contracts'
import ContractSignature from './pages/ContractSignature'
import Riders from './pages/Riders'
import Suppliers from './pages/Suppliers'
import Settings from './pages/Settings'
import Trash from './pages/Trash'
import Menus from './pages/Menus'
import DeliveryZones from './pages/DeliveryZones'
import PartnerProtection from './pages/PartnerProtection'
import Login from './pages/Login'

function applyTheme() {
  const theme = localStorage.getItem('theme') || 'system'
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
  if (theme === 'dark' || (theme === 'system' && prefersDark)) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
}

function App() {
  useEffect(() => {
    applyTheme()
    const mq = window.matchMedia('(prefers-color-scheme: dark)')
    const handler = () => applyTheme()
    mq.addEventListener('change', handler)
    return () => mq.removeEventListener('change', handler)
  }, [])

  return (
    <AuthProvider>
      <PushNotificationManager />
      <BrowserRouter
        future={{
          v7_startTransition: true,
          v7_relativeSplatPath: true
        }}
      >
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/sign/:token" element={<ContractSignature />} />
          <Route path="/" element={
            <PrivateRoute>
              <Layout />
            </PrivateRoute>
          }>
            <Route index element={
              <ProtectedRoute permission="dashboard">
                <Dashboard />
              </ProtectedRoute>
            } />
            <Route path="clients" element={
              <ProtectedRoute permission="clients">
                <Clients />
              </ProtectedRoute>
            } />
            <Route path="leads" element={
              <ProtectedRoute permission="clients">
                <Leads />
              </ProtectedRoute>
            } />
            <Route path="orders" element={
              <ProtectedRoute permission="orders">
                <Orders />
              </ProtectedRoute>
            } />
            <Route path="deliveries" element={
              <ProtectedRoute permission="deliveries">
                <Deliveries />
              </ProtectedRoute>
            } />
            <Route path="invoices" element={
              <ProtectedRoute permission="invoices">
                <Invoices />
              </ProtectedRoute>
            } />
            <Route path="payments" element={
              <ProtectedRoute permission="invoices">
                <Payments />
              </ProtectedRoute>
            } />
            <Route path="tasks" element={
              <ProtectedRoute permission="tasks">
                <Tasks />
              </ProtectedRoute>
            } />
            <Route path="contracts" element={
              <ProtectedRoute permission="contracts">
                <Contracts />
              </ProtectedRoute>
            } />
            <Route path="riders" element={
              <ProtectedRoute permission="riders">
                <Riders />
              </ProtectedRoute>
            } />
            <Route path="suppliers" element={
              <ProtectedRoute permission="invoices">
                <Suppliers />
              </ProtectedRoute>
            } />
            <Route path="menus" element={
              <ProtectedRoute permission="menu">
                <Menus />
              </ProtectedRoute>
            } />
            <Route path="delivery-zones" element={
              <ProtectedRoute permission="orders">
                <DeliveryZones />
              </ProtectedRoute>
            } />
            <Route path="partner-protection" element={
              <ProtectedRoute permission="orders">
                <PartnerProtection />
              </ProtectedRoute>
            } />
            <Route path="settings" element={<Settings />} />
            <Route path="trash" element={<Trash />} />
          </Route>
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  )
}

export default App
