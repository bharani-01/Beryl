import React from 'react';
import { ArrowUp } from 'lucide-react';

export default function Footer() {
  const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  return (
    <footer className="border-t border-stone-200/60 bg-[#FDFCF8] py-12 text-xs text-stone-500">
      <div className="mx-auto max-w-4xl px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-6">
        
        {/* Brand */}
        <div className="flex items-center gap-2.5">
          <div className="flex size-6 items-center justify-center rounded-full bg-[#FFB7B2]">
            <span className="size-1.5 rounded-full bg-white"></span>
          </div>
          <span className="text-sm font-semibold text-[#292524]">Beryl</span>
          <span className="text-stone-300">·</span>
          <span>v1.0.0</span>
          <span className="text-stone-300">·</span>
          <span className="text-stone-400">Tactile self-hosting</span>
        </div>

        {/* Links */}
        <div className="flex items-center gap-6">
          <a href="/login" className="hover:text-[#292524] transition-colors">
            Console
          </a>
          <a 
            href="https://github.com/bharani-01/Beryl" 
            target="_blank" 
            rel="noopener noreferrer"
            className="hover:text-[#292524] transition-colors"
          >
            GitHub
          </a>
          <button
            onClick={scrollToTop}
            className="hover:text-[#292524] transition-colors flex items-center gap-1 cursor-pointer"
          >
            <span>Top</span>
            <ArrowUp className="size-3" />
          </button>
        </div>

      </div>
    </footer>
  );
}
