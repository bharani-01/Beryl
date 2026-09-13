import React, { useState } from 'react';
import { ArrowRight, CheckCircle2 } from 'lucide-react';

export default function WaitlistConversion() {
  const [email, setEmail] = useState('');
  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = (e) => {
    e.preventDefault();
    if (email.trim()) {
      setSubmitted(true);
    }
  };

  return (
    <section className="relative py-24 sm:py-32 overflow-hidden border-t border-stone-200/60 text-center">
      
      {/* High-blur floating background gradients */}
      <div 
        className="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 size-[600px] rounded-full bg-[#FFE4E1] opacity-40 blur-[120px] -z-10"
        aria-hidden="true"
      />
      <div 
        className="pointer-events-none absolute top-1/3 left-1/4 size-[400px] rounded-full bg-[#E8EFE8] opacity-50 blur-[100px] -z-10"
        aria-hidden="true"
      />
      <div 
        className="pointer-events-none absolute bottom-10 right-1/4 size-[450px] rounded-full bg-[#EFEDF4] opacity-50 blur-[110px] -z-10"
        aria-hidden="true"
      />

      <div className="relative mx-auto max-w-2xl px-4 sm:px-6">
        
        {/* Dark stone #292524 rounded-square icon with a coral dot */}
        <div className="mx-auto mb-6 flex size-14 items-center justify-center rounded-2xl bg-[#292524] shadow-md">
          <span className="size-3 rounded-full bg-[#FFB7B2]"></span>
        </div>

        <h2 className="text-3xl sm:text-5xl font-medium tracking-tight text-[#292524] leading-tight">
          Ready for a quieter cloud?
        </h2>

        <p className="mt-4 text-sm sm:text-base text-[#78716C] max-w-md mx-auto leading-relaxed">
          Create your digital living room today. Experience the joy of self-hosting with zero anxiety.
        </p>

        {/* Conversion Form */}
        <div className="mt-8 max-w-md mx-auto">
          {submitted ? (
            <div className="rounded-2xl border border-stone-200 bg-white/90 p-5 shadow-sm backdrop-blur-md flex items-center justify-center gap-2.5 text-[#292524]">
              <CheckCircle2 className="size-5 text-emerald-600" />
              <span className="text-sm font-medium">Welcome aboard. Redirecting to your console...</span>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="flex flex-col sm:flex-row items-center gap-2.5">
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="Enter your email address..."
                className="w-full rounded-full bg-stone-50 border border-stone-200 px-6 py-4 text-sm text-[#292524] placeholder-stone-400 focus:outline-none focus:border-stone-400 focus:bg-white transition-all shadow-xs"
              />
              <button
                type="submit"
                className="w-full sm:w-auto shrink-0 rounded-full bg-[#292524] text-white px-8 py-4 text-sm font-medium hover:bg-stone-800 hover:scale-105 active:scale-95 transition-transform duration-200 shadow-md flex items-center justify-center gap-2"
              >
                <span>Get started</span>
                <ArrowRight className="size-4 text-stone-300" />
              </button>
            </form>
          )}

          <p className="mt-4 text-xs text-stone-400">
            Open-source under MIT / Apache 2.0. Your data stays yours forever.
          </p>
        </div>

      </div>
    </section>
  );
}
