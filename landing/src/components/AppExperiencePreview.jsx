import React, { useState } from 'react';
import { Database, Shield, Globe, Check, Layers, Play, Sparkles, Heart } from 'lucide-react';

export default function AppExperiencePreview() {
  const [breatheCount, setBreatheCount] = useState(1);

  return (
    <section id="experience" className="py-20 sm:py-28 overflow-hidden">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center mb-16">
        <div className="inline-flex items-center gap-2 rounded-full border border-stone-200/80 bg-white px-3 py-1 text-xs font-medium text-[#78716C] shadow-sm mb-3">
          <Heart className="size-3 text-[#FFB7B2] fill-current" />
          <span>Tactile living room interface</span>
        </div>
        <h2 className="text-3xl sm:text-5xl font-medium tracking-tight text-[#292524]">
          Serene, tactile, and intentionally calm.
        </h2>
        <p className="mt-3 text-sm sm:text-base text-[#78716C] max-w-md mx-auto">
          Deploy free open-source tools and databases with one tap. No blinking sirens or alarmist alerts.
        </p>
      </div>

      {/* Three-mockup stacked cascading layout */}
      <div className="relative mx-auto max-w-5xl px-4 flex flex-col md:flex-row items-center justify-center gap-6 md:gap-4 pb-16">
        
        {/* Left Phone: 280x580px, 80% opacity, translated +48px, Sage #E8EFE8 */}
        <div className="w-[280px] h-[580px] rounded-[2.5rem] border-4 border-white bg-[#E8EFE8] p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.06)] opacity-80 md:translate-y-12 transition-all flex flex-col justify-between shrink-0">
          <div>
            {/* Phone speaker/notch */}
            <div className="mx-auto h-3 w-16 rounded-full bg-stone-300/60 mb-4"></div>
            
            {/* Screen Header */}
            <div className="text-left mb-4">
              <span className="text-[10px] font-medium text-stone-500 uppercase tracking-wider">Database Vault</span>
              <h4 className="text-base font-semibold text-[#292524]">Stateful engines</h4>
            </div>

            {/* Sage Screen Items */}
            <div className="space-y-2.5 text-left text-xs">
              <div className="rounded-2xl bg-white/80 p-3 shadow-xs">
                <div className="flex items-center justify-between font-medium text-[#292524]">
                  <span>PostgreSQL 17</span>
                  <span className="text-[10px] bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">Healthy</span>
                </div>
                <p className="text-[11px] text-stone-500 mt-1">Snapshot saved 12m ago to S3</p>
              </div>

              <div className="rounded-2xl bg-white/80 p-3 shadow-xs">
                <div className="flex items-center justify-between font-medium text-[#292524]">
                  <span>Redis Cache</span>
                  <span className="text-[10px] bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">Active</span>
                </div>
                <p className="text-[11px] text-stone-500 mt-1">Memory usage: 48MB / 1GB</p>
              </div>

              <div className="rounded-2xl bg-white/80 p-3 shadow-xs">
                <div className="flex items-center justify-between font-medium text-[#292524]">
                  <span>Point-in-time log</span>
                  <span className="text-[10px] text-stone-400">Continuous</span>
                </div>
                <p className="text-[11px] text-stone-500 mt-1">Zero data loss replication</p>
              </div>
            </div>
          </div>

          <div className="rounded-2xl bg-white/70 p-3 text-left text-[11px] text-stone-600">
            <span>Encrypted volume attached</span>
          </div>
        </div>

        {/* Center Phone: 300x620px, fully opaque, pulsing Coral #FFB7B2 'Breathe' button */}
        <div className="w-[300px] h-[620px] rounded-[2.5rem] border-4 border-white bg-white p-5 shadow-[0_12px_40px_-4px_rgba(0,0,0,0.1)] opacity-100 z-20 flex flex-col justify-between shrink-0 relative">
          <div>
            {/* Notch */}
            <div className="mx-auto h-3.5 w-20 rounded-full bg-stone-200 mb-5"></div>
            
            {/* Topbar */}
            <div className="flex items-center justify-between mb-5 text-left">
              <div>
                <span className="text-[11px] text-stone-400">Connected node</span>
                <p className="text-base font-bold text-[#292524]">Beryl Core 01</p>
              </div>
              <div className="size-2.5 rounded-full bg-[#FFB7B2] animate-pulse"></div>
            </div>

            {/* Central peaceful dashboard widget */}
            <div className="space-y-3 text-left">
              <div className="rounded-3xl border border-stone-100 bg-[#FDFCF8] p-4">
                <span className="text-[10.5px] font-medium text-stone-400 uppercase">Open-Source Rhythm</span>
                <p className="text-2xl font-bold text-[#292524] mt-0.5">3 of 3 Active</p>
                <div className="mt-2.5 flex items-center gap-1.5 text-xs text-stone-500">
                  <span className="size-1.5 rounded-full bg-emerald-500"></span>
                  <span>Supabase, Ghost & Postgres live</span>
                </div>
              </div>

              {/* Resource cards */}
              <div className="rounded-2xl border border-stone-100 p-3 bg-white">
                <div className="flex items-center justify-between text-xs">
                  <span className="font-semibold text-[#292524]">supabase-auth-db</span>
                  <span className="text-emerald-600 font-medium text-[10.5px]">Running</span>
                </div>
                <span className="text-[10.5px] text-stone-400">auth.trackifyapp.co.in</span>
              </div>

              <div className="rounded-2xl border border-stone-100 p-3 bg-white">
                <div className="flex items-center justify-between text-xs">
                  <span className="font-semibold text-[#292524]">ghost-publishing</span>
                  <span className="text-emerald-600 font-medium text-[10.5px]">Running</span>
                </div>
                <span className="text-[10.5px] text-stone-400">blog.trackifyapp.co.in</span>
              </div>
            </div>
          </div>

          {/* Pulsing Coral 'Breathe' button at bottom */}
          <div className="pt-4 border-t border-stone-100 text-center">
            <button
              onClick={() => setBreatheCount((c) => c + 1)}
              className="w-full animate-breathe rounded-full bg-[#FFB7B2] text-[#292524] py-3 text-xs font-semibold shadow-sm hover:scale-105 active:scale-95 transition-all flex items-center justify-center gap-1.5"
            >
              <Sparkles className="size-3.5 text-[#292524]" />
              <span>Breathe ({breatheCount})</span>
            </button>
            <p className="text-[10px] text-stone-400 mt-2">Tap to steady your pulse</p>
          </div>
        </div>

        {/* Right Phone: 280x580px, 80% opacity, translated +96px, Lavender #EFEDF4 */}
        <div className="w-[280px] h-[580px] rounded-[2.5rem] border-4 border-white bg-[#EFEDF4] p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.06)] opacity-80 md:translate-y-24 transition-all flex flex-col justify-between shrink-0">
          <div>
            {/* Notch */}
            <div className="mx-auto h-3 w-16 rounded-full bg-stone-300/60 mb-4"></div>
            
            {/* Screen Header */}
            <div className="text-left mb-4">
              <span className="text-[10px] font-medium text-stone-500 uppercase tracking-wider">Ingress & Edge</span>
              <h4 className="text-base font-semibold text-[#292524]">Traefik routing</h4>
            </div>

            {/* Lavender Screen Items */}
            <div className="space-y-2.5 text-left text-xs">
              <div className="rounded-2xl bg-white/80 p-3 shadow-xs">
                <div className="flex items-center justify-between font-medium text-[#292524]">
                  <span>Let's Encrypt TLS</span>
                  <span className="text-[10px] text-purple-700 bg-purple-100 px-2 py-0.5 rounded-full">Auto-renew</span>
                </div>
                <p className="text-[11px] text-stone-500 mt-1">Valid for next 82 days</p>
              </div>

              <div className="rounded-2xl bg-white/80 p-3 shadow-xs">
                <div className="flex items-center justify-between font-medium text-[#292524]">
                  <span>Dynamic Ingress</span>
                  <span className="text-[10px] text-purple-700 bg-purple-100 px-2 py-0.5 rounded-full">0 Dropped</span>
                </div>
                <p className="text-[11px] text-stone-500 mt-1">Traefik v3 HTTP router</p>
              </div>

              <div className="rounded-2xl bg-white/80 p-3 shadow-xs">
                <div className="flex items-center justify-between font-medium text-[#292524]">
                  <span>Audit verification</span>
                  <span className="text-[10px] text-emerald-600 font-mono">Verified</span>
                </div>
                <p className="text-[11px] text-stone-500 mt-1">SHA-256 chain intact</p>
              </div>
            </div>
          </div>

          <div className="rounded-2xl bg-white/70 p-3 text-left text-[11px] text-stone-600">
            <span>SSL certificate ready</span>
          </div>
        </div>

      </div>
    </section>
  );
}
