import React from 'react';
import { 
  BookOpen, 
  ArrowRight, 
  CheckCircle, 
  Scale, 
  FileText, 
  DollarSign, 
  ShieldAlert, 
  Users, 
  Compass, 
  FolderArchive,
  Landmark,
  Award,
  ShieldCheck
} from 'lucide-react';

export default function PracticeSelection({ axes = [], onSelectAxis, onBack }) {
  const getIcon = (category, name) => {
    if (name.includes('Acciones de Control')) return <ShieldAlert className="w-6 h-6 text-indigo-600" />;
    if (name.includes('ABC del LA') || name.includes('LA - FT')) return <DollarSign className="w-6 h-6 text-rose-600" />;
    if (name.includes('Constitución')) return <Landmark className="w-6 h-6 text-blue-800" />;
    if (name.includes('Carrera Administrativa')) return <Award className="w-6 h-6 text-purple-700" />;
    if (category === 'integridad' || name.includes('Integridad')) return <ShieldCheck className="w-6 h-6 text-emerald-700" />;
    if (name.includes('Tributario')) return <Scale className="w-6 h-6 text-blue-600" />;
    if (name.includes('Aduanera')) return <ShieldAlert className="w-6 h-6 text-amber-600" />;
    if (name.includes('Cambiario')) return <DollarSign className="w-6 h-6 text-emerald-600" />;
    if (name.includes('CPACA') || name.includes('Administrativo')) return <FileText className="w-6 h-6 text-purple-600" />;
    if (name.includes('Evasión') || name.includes('Transparencia')) return <Compass className="w-6 h-6 text-indigo-600" />;
    if (name.includes('Documental') || name.includes('MIPG')) return <FolderArchive className="w-6 h-6 text-teal-600" />;
    if (category === 'comportamental') return <Users className="w-6 h-6 text-sky-600" />;
    return <BookOpen className="w-6 h-6 text-slate-600" />;
  };

  return (
    <div className="max-w-5xl mx-auto space-y-6 pb-16">
      
      <div className="space-y-2">
        <h1 className="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
          <BookOpen className="w-7 h-7 text-blue-700" />
          <span>Entrenamiento Focalizado por Eje Temático</span>
        </h1>
        <p className="text-xs sm:text-sm text-slate-600 max-w-3xl leading-relaxed">
          En este modo puedes entrenar temas específicos de la convocatoria DIAN 2676. A diferencia del simulacro formal, aquí recibes <strong>retroalimentación inmediata</strong> con el fundamento jurídico y la explicación de cada opción tan pronto seleccionas tu respuesta.
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {axes.map((axis) => {
          return (
            <div
              key={axis.id}
              className="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md hover:border-blue-400 transition-all flex flex-col justify-between group"
            >
              <div className="space-y-3">
                <div className="flex items-center justify-between">
                  <div className="p-3 bg-slate-50 rounded-xl border border-slate-100 group-hover:bg-blue-50 transition-colors">
                    {getIcon(axis.category, axis.name)}
                  </div>
                  <div className="flex items-center space-x-2">
                    <span className="text-[11px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 uppercase">
                      {axis.category.replace('_', ' ')}
                    </span>
                    {axis.weight_pct > 0 && (
                      <span className="text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">
                        Peso: {axis.weight_pct}%
                      </span>
                    )}
                  </div>
                </div>

                <div>
                  <h3 className="font-bold text-slate-900 text-base group-hover:text-blue-700 transition-colors">
                    {axis.name}
                  </h3>
                  <p className="text-xs text-slate-500 mt-1 leading-relaxed">
                    {axis.description}
                  </p>
                </div>
              </div>

              <div className="pt-4 border-t border-slate-100 mt-4 flex items-center justify-between">
                <span className="text-xs font-semibold text-slate-500">
                  {axis.question_count || 0} preguntas disponibles
                </span>
                <button
                  onClick={() => onSelectAxis(axis.id)}
                  className="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white rounded-xl text-xs font-bold transition-colors flex items-center space-x-1.5 shadow-sm"
                >
                  <span>Practicar Eje</span>
                  <ArrowRight className="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          );
        })}
      </div>

    </div>
  );
}
