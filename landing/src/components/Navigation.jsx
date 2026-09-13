import React, { useState } from 'react';
import { Menu, X, ArrowRight } from 'lucide-react';

export default function Navigation() {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  return (
    <nav className="fixed top-5 left-0 right-0 z-40 mx-auto w-[calc(100%-32px)] max-w-4xl transition-all">
      <div className="flex h-14 items-center justify-between rounded-full border border-stone-200/60 bg-white/70 px-4 sm:px-6 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] backdrop-blur-[20px]">
        
        {/* Brand with small coral circular logo */}
        <a href="/" className="flex items-center gap-2.5 transition-opacity hover:opacity-80">
          <div className="flex size-7 items-center justify-center rounded-full bg-[#FFB7B2]">
            <span className="size-2 rounded-full bg-white"></span>
          </div>
          <span className="text-[15px] font-semibold tracking-tight text-[#292524]">Beryl</span>
        </a>

        {/* Text links in 14px Outfit Medium */}
        <div className="hidden md:flex items-center gap-6 text-[14px] font-medium text-[#78716C]">
          <a href="#templates" className="hover:text-[#292524] transition-colors">Open source</a>
          <a href="#experience" className="hover:text-[#292524] transition-colors">Experience</a>
          <a href="#scenarios" className="hover:text-[#292524] transition-colors">Moments</a>
          <a href="#testimonials" className="hover:text-[#292524] transition-colors">Stories</a>
          <a href="#faq" className="hover:text-[#292524] transition-colors">Answers</a>
        </div>

        {/* Dark stone #292524 pill CTA button */}
        <div className="hidden md:flex items-center gap-3">
          <a
            href="/login"
            className="text-[13.5px] font-medium text-[#78716C] hover:text-[#292524] transition-colors px-2 py-1"
          >
            Sign in
          </a>
          <a
            href="/login"
            className="inline-flex items-center gap-1.5 rounded-full bg-[#292524] px-4 py-2 text-[13px] font-medium text-white shadow-sm hover:bg-stone-800 transition-all hover:scale-[1.02] active:scale-[0.98]"
          >
            <span>Open console</span>
            <ArrowRight className="size-3.5 text-stone-300" />
          </a>
        </div>

        {/* Mobile toggle */}
        <div className="flex md:hidden">
          <button
            type="button"
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
            className="flex size-8 items-center justify-center rounded-full text-[#292524] hover:bg-stone-100 transition-colors"
            aria-label="Toggle menu"
          >
            {mobileMenuOpen ? <X className="size-4" /> : <Menu className="size-4" />}
          </button>
        </div>
      </div>

      {/* Mobile Drawer */}
      {mobileMenuOpen && (
        <div className="mt-2 rounded-3xl border border-stone-200/60 bg-white/95 p-5 shadow-lg backdrop-blur-xl md:hidden space-y-4">
          <div className="flex flex-col space-y-3 text-[14px] font-medium text-[#78716C]">
            <a href="#templates" onClick={() => setMobileMenuOpen(false)} className="hover:text-[#292524]">Open source</a>
            <a href="#experience" onClick={() => setMobileMenuOpen(false)} className="hover:text-[#292524]">Experience</a>
            <a href="#scenarios" onClick={() => setMobileMenuOpen(false)} className="hover:text-[#292524]">Moments</a>
            <a href="#testimonials" onClick={() => setMobileMenuOpen(false)} className="hover:text-[#292524]">Stories</a>
            <a href="#faq" onClick={() => setMobileMenuOpen(false)} className="hover:text-[#292524]">Answers</a>
          </div>
          <div className="pt-3 border-t border-stone-100 flex items-center justify-between gap-3">
            <a href="/login" className="text-sm font-medium text-[#78716C]">Sign in</a>
            <a href="/login" className="rounded-full bg-[#292524] px-4 py-2 text-xs font-medium text-white">
              Open console
            </a>
          </div>
        </div>
      )}
    </nav>
  );
}
