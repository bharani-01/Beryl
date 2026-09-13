import React from 'react';
import { ArrowRight, Sparkles, Layers } from 'lucide-react';

export default function Hero() {
  return (
    <section className="relative pt-32 pb-16 sm:pt-40 sm:pb-24 overflow-hidden text-center">
      
      {/* Two large blurred background blobs (#FFE4E1 and #E6E6FA) at 60% opacity with fluid floating animation */}
      <div 
        className="pointer-events-none absolute top-12 left-1/2 -translate-x-[75%] size-[380px] sm:size-[520px] rounded-full bg-[#FFE4E1] opacity-60 blur-[90px] animate-float-slow -z-10"
        aria-hidden="true"
      />
      <div 
        className="pointer-events-none absolute top-28 left-1/2 translate-x-[5%] size-[360px] sm:size-[480px] rounded-full bg-[#E6E6FA] opacity-60 blur-[95px] animate-float-reverse -z-10"
        aria-hidden="true"
      />

      <div className="relative mx-auto max-w-4xl px-4 sm:px-6">
        
        {/* Ambient indicator emphasizing free open-source software */}
        <div className="inline-flex items-center gap-2 rounded-full border border-stone-200/80 bg-white/80 px-3.5 py-1 text-xs font-medium text-[#78716C] shadow-sm backdrop-blur-md mb-8">
          <span className="size-2 rounded-full bg-[#FFB7B2]"></span>
          <span>Deploy any free open-source software with 1-click</span>
        </div>

        {/* Headline: 72px Outfit with cursive Reenie Beanie word */}
        <h1 className="text-4xl sm:text-6xl md:text-[72px] font-medium tracking-tight text-[#292524] leading-[1.08] max-w-3xl mx-auto">
          Deploy any free{' '}
          <span className="font-cursive text-5xl sm:text-7xl md:text-[86px] text-[#FFB7B2] font-normal inline-block transform -rotate-2 -translate-y-1 mx-1.5">
            open-source
          </span>
          software, with peace of mind.
        </h1>

        {/* Sub-headline max-width 500px */}
        <p className="mt-6 text-base sm:text-lg text-[#78716C] leading-relaxed max-w-[520px] mx-auto font-normal">
          From Supabase and Ghost to WordPress and Postgres. Launch 300+ free open-source software stacks and custom apps on your own servers with zero vendor lock-in.
        </p>

        {/* Dual CTA buttons */}
        <div className="mt-9 flex flex-col sm:flex-row items-center justify-center gap-3.5">
          <a
            href="/login"
            className="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-full bg-[#FFB7B2] px-8 py-3.5 text-[15px] font-medium text-[#292524] shadow-[0_4px_20px_-2px_rgba(255,183,178,0.5)] hover:shadow-[0_8px_25px_-2px_rgba(255,183,178,0.7)] hover:scale-[1.02] active:scale-[0.98] transition-all"
          >
            <span>Deploy open source</span>
            <ArrowRight className="size-4 text-[#292524]/80" />
          </a>

          <a
            href="#templates"
            className="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-full border border-stone-200 bg-white px-8 py-3.5 text-[15px] font-medium text-[#292524] hover:bg-stone-50 hover:border-stone-300 transition-all shadow-sm"
          >
            <Layers className="size-4 text-stone-400" />
            <span>Browse 300+ software</span>
          </a>
        </div>

        {/* Quiet micro-details */}
        <div className="mt-10 flex items-center justify-center gap-6 text-xs text-[#78716C]">
          <span>300+ free open-source software</span>
          <span className="text-stone-300">•</span>
          <span>1-click automatic SSL</span>
          <span className="text-stone-300">•</span>
          <span>Zero cloud markups</span>
        </div>

      </div>
    </section>
  );
}
