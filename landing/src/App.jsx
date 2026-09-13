import React from 'react';
import GrainOverlay from './components/GrainOverlay';
import Navigation from './components/Navigation';
import Hero from './components/Hero';
import HorizontalScenarioScroll from './components/HorizontalScenarioScroll';
import SupportedTemplates from './components/SupportedTemplates';
import AppExperiencePreview from './components/AppExperiencePreview';
import DiaryTestimonials from './components/DiaryTestimonials';
import InteractiveFaqAccordion from './components/InteractiveFaqAccordion';
import WaitlistConversion from './components/WaitlistConversion';
import Footer from './components/Footer';

export default function App() {
  return (
    <div className="min-h-screen bg-[#FDFCF8] text-[#292524] relative selection:bg-[#FFB7B2]/30 selection:text-[#292524]">
      {/* Global Grain Texture Layer */}
      <GrainOverlay />

      {/* Floating Pill Navigation */}
      <Navigation />

      {/* Main Single-Column Flow */}
      <main>
        <Hero />
        <HorizontalScenarioScroll />
        <SupportedTemplates />
        <AppExperiencePreview />
        <DiaryTestimonials />
        <InteractiveFaqAccordion />
        <WaitlistConversion />
      </main>

      {/* Tactile Footer */}
      <Footer />
    </div>
  );
}
