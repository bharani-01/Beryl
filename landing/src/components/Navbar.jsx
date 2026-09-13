import React, { useState } from 'react';
import { Shield, Terminal, Server, Layers, Cpu, Github, Menu, X, ArrowRight, Sparkles } from 'lucide-react';

export default function Navbar() {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  return (
    <header className="sticky top-0 z-50 w-full border-b border-white/[0.08] bg-[#07090e]/80 backdrop-blur-xl transition-all">
      <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        
        {/* Brand & Version Badge */}
        <div className="flex items-center gap-3">
          <a href="/" className="flex items-center gap-2.5 transition-opacity hover:opacity-90">
            <img src="/beryl-logo.png" alt="Beryl" className="h-7 w-7 object-contain" />
            <span className="text-lg font-bold tracking-tight text-white">Beryl</span>
          </a>
          <a 
            href="https://github.com/bharani-01/Beryl" 
            target="_blank" 
            rel="noopener noreferrer"
            className="inline-flex items-center gap-1 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[11px] font-medium text-emerald-400 hover:bg-emerald-500/20 transition-colors"
          >
            <span className="size-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            v1.0.0
          </a>
        </div>

        {/* Desktop Navigation Links */}
        <nav className="hidden md:flex items-center gap-8 text-[13.5px] font-medium text-neutral-400">
          <a href="#features" className="hover:text-white transition-colors">Features</a>
          <a href="#architecture" className="hover:text-white transition-colors">Architecture</a>
          <a href="#simulator" className="hover:text-white transition-colors">Interactive Demo</a>
          <a href="#templates" className="hover:text-white transition-colors">Templates</a>
          <a href="#pricing" className="hover:text-white transition-colors">Pricing</a>
          <a 
            href="https://github.com/bharani-01/Beryl" 
            target="_blank" 
            rel="noopener noreferrer" 
            className="flex items-center gap-1.5 hover:text-white transition-colors"
          >
            <Github className="size-3.5" />
            GitHub
          </a>
        </nav>

        {/* Auth CTAs */}
        <div className="hidden md:flex items-center gap-3">
          <a
            href="/login"
            className="rounded-lg px-3.5 py-1.5 text-[13px] font-medium text-neutral-300 hover:text-white transition-colors"
          >
            Sign In
          </a>
          <a
            href="/login"
            className="group relative inline-flex items-center gap-2 overflow-hidden rounded-lg bg-emerald-500 px-4 py-2 text-[13px] font-semibold text-neutral-950 transition-all hover:bg-emerald-400 active:scale-[0.98] shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30"
          >
            <span>Launch Console</span>
            <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-0.5" />
          </a>
        </div>

        {/* Mobile menu button */}
        <div className="flex md:hidden">
          <button
            type="button"
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
            className="inline-flex size-9 items-center justify-center rounded-lg border border-white/[0.08] text-neutral-400 hover:text-white"
            aria-label="Toggle navigation"
          >
            {mobileMenuOpen ? <X className="size-5" /> : <Menu className="size-5" />}
          </button>
        </div>
      </div>

      {/* Mobile Drawer */}
      {mobileMenuOpen && (
        <div className="border-b border-white/[0.08] bg-[#0c1018] px-4 py-5 md:hidden space-y-4">
          <div className="flex flex-col space-y-3 text-sm font-medium text-neutral-300">
            <a href="#features" onClick={() => setMobileMenuOpen(false)} className="hover:text-white">Features</a>
            <a href="#architecture" onClick={() => setMobileMenuOpen(false)} className="hover:text-white">Architecture</a>
            <a href="#simulator" onClick={() => setMobileMenuOpen(false)} className="hover:text-white">Interactive Demo</a>
            <a href="#templates" onClick={() => setMobileMenuOpen(false)} className="hover:text-white">Templates</a>
            <a href="#pricing" onClick={() => setMobileMenuOpen(false)} className="hover:text-white">Pricing</a>
            <a href="https://github.com/bharani-01/Beryl" target="_blank" rel="noopener noreferrer" className="flex items-center gap-1.5 hover:text-white">
              <Github className="size-4" /> GitHub Repository
            </a>
          </div>
          <div className="pt-3 border-t border-white/[0.08] flex flex-col gap-2.5">
            <a
              href="/login"
              className="w-full text-center rounded-lg border border-white/[0.1] py-2 text-sm font-medium text-neutral-200"
            >
              Sign In
            </a>
            <a
              href="/login"
              className="w-full text-center rounded-lg bg-emerald-500 py-2 text-sm font-semibold text-neutral-950"
            >
              Launch Console
            </a>
          </div>
        </div>
      )}
    </header>
  );
}
