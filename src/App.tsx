import React from 'react';
import { HashRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import Layout from './components/Layout';
import ScrollToTop from './components/ScrollToTop';
import Home from './pages/Home';
import Docs from './pages/Docs';
import Security from './pages/Security';
import Changelog from './pages/Changelog';
import Author from './pages/Author';
import Donate from './pages/Donate';

const App: React.FC = () => {
    return (
        <Router>
            <ScrollToTop />
            <Routes>
                <Route element={<Layout />}>
                    <Route path="/" element={<Home />} />
                    <Route path="/docs" element={<Docs />} />
                    <Route path="/security" element={<Security />} />
                    <Route path="/changelog" element={<Changelog />} />
                    <Route path="/author" element={<Author />} />
                    <Route path="/donate" element={<Donate />} />
                    <Route path="*" element={<Navigate to="/" replace />} />
                </Route>
            </Routes>
        </Router>
    );
};

export default App;
