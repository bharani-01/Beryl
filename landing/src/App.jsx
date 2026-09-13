import React from 'react';
import Navbar from './components/Navbar';
import Hero from './components/Hero';
import ArchitectureFlow from './components/ArchitectureFlow';
import FeaturesGrid from './components/FeaturesGrid';
import InteractiveLivePreview from './components/InteractiveLivePreview';
import SupportedTemplates from './components/SupportedTemplates';
import PricingCalculator from './components/PricingCalculator';
import Footer from './components/Footer';

export default function App() {
  return (
    <div className="min-h-screen bg-[#07090e] text-[#e2e8f0] selection:bg-emerald-500/20 selection:text-emerald-300">
      <Navbar />
      <main>
        <Hero />
        <ArchitectureFlow />
        <FeaturesGrid />
        <InteractiveLivePreview />
        <SupportedTemplates />
        <PricingCalculator />
      </main>
      <Footer />
    </div>
  );
}
