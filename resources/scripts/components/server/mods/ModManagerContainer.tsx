import React, { useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Spinner from '@/components/elements/Spinner';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faDownload, faFolder, faPlus, faTrash, faTimes } from '@fortawesome/free-solid-svg-icons';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import http from '@/api/http';

interface InstalledMod {
    name: string;
    fileName: string;
    size: number;
    version?: string;
    lastModified: string;
}

interface AvailableMod {
    id: number;
    name: string;
    slug: string;
    summary: string;
    download_count: number;
    logo_url?: string;
    categories: string[];
    latest_version?: {
        id: number;
        display_name: string;
        file_name: string;
        download_url: string;
        game_versions: string[];
        release_type: number;
    };
}

const ModManagerContainer = () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const [loading, setLoading] = useState(true);
    const [installedMods, setInstalledMods] = useState<InstalledMod[]>([]);
    const [availableMods, setAvailableMods] = useState<AvailableMod[]>([]);
    const [showBrowser, setShowBrowser] = useState(false);
    const [installing, setInstalling] = useState<number | null>(null);
    const [searchTerm, setSearchTerm] = useState('');

    // Load installed mods from the mods/ directory
    const loadInstalledMods = async () => {
        try {
            console.log('ModManager: Making API call to:', `/api/client/servers/${uuid}/mods/installed`);
            const { data } = await http.get(`/api/client/servers/${uuid}/mods/installed`);
            console.log('ModManager: API response received:', data);

            if (data.debug) {
                console.log('ModManager: Debug response received:', data);
                if (data.error) {
                    console.error('ModManager: API Error:', data.message);
                    console.error('ModManager: API Trace:', data.trace);
                } else {
                    console.log('ModManager: Debug info:', data.message);
                    console.log('ModManager: Attempted paths:', data.attempted_paths);
                    console.log('ModManager: Server info:', data.server_info);
                }
                return; // Don't update state with debug data
            }

            setInstalledMods(data);
        } catch (error) {
            console.error('ModManager: Failed to load installed mods:', error);
            if (error && typeof error === 'object' && 'response' in error) {
                const apiError = error as any;
                console.error('ModManager: Error response:', apiError.response?.data);
                console.error('ModManager: Error status:', apiError.response?.status);
            }
        }
    };

    // Load available mods from the database
    const loadAvailableMods = async () => {
        try {
            const { data } = await http.get(`/api/client/servers/${uuid}/mods/available`);
            setAvailableMods(data);
        } catch (error) {
            console.error('Failed to load available mods:', error);
        }
    };

    // Install a mod
    const installMod = async (mod: AvailableMod) => {
        if (!mod.latest_version) return;

        setInstalling(mod.id);
        try {
            await http.post(`/api/client/servers/${uuid}/mods/install`, {
                mod_id: mod.id,
                file_id: mod.latest_version.id,
            });

            // Refresh installed mods
            await loadInstalledMods();
        } catch (error) {
            console.error('Failed to install mod:', error);
        } finally {
            setInstalling(null);
        }
    };

    // Uninstall a mod
    const uninstallMod = async (fileName: string) => {
        if (!confirm(`Are you sure you want to uninstall ${fileName}?`)) return;

        try {
            await http.delete(`/api/client/servers/${uuid}/mods/uninstall`, {
                data: { file_name: fileName },
            });

            // Refresh installed mods
            await loadInstalledMods();
        } catch (error) {
            console.error('Failed to uninstall mod:', error);
        }
    };

    useEffect(() => {
        const loadData = async () => {
            setLoading(true);
            await Promise.all([loadInstalledMods(), loadAvailableMods()]);
            setLoading(false);
        };

        loadData();
    }, [uuid]);

    const filteredMods = availableMods.filter(
        (mod) =>
            mod.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
            mod.summary.toLowerCase().includes(searchTerm.toLowerCase())
    );

    if (loading) {
        return <Spinner size={'large'} centered />;
    }

    return (
        <div css={tw`w-full max-w-7xl mx-auto px-4`}>
            {/* Installed Mods Section */}
            <TitledGreyBox title={`Installed Mods (${installedMods.length})`} css={tw`mb-6`}>
                <div css={tw`flex justify-between items-center mb-4`}>
                    <p css={tw`text-sm text-neutral-400`}>
                        Mods currently installed in your server&apos;s{' '}
                        <code css={tw`bg-neutral-900 px-2 py-1 rounded text-xs`}>mods/</code> directory
                    </p>
                    <Button type={'button'} size={'small'} color={'primary'} onClick={() => setShowBrowser(true)}>
                        <FontAwesomeIcon icon={faPlus} css={tw`mr-2`} />
                        Install Mods
                    </Button>
                </div>

                {installedMods.length === 0 ? (
                    <div css={tw`text-center py-12 text-neutral-400`}>
                        <FontAwesomeIcon icon={faFolder} css={tw`text-6xl mb-4 text-neutral-500`} />
                        <h3 css={tw`text-lg font-medium text-neutral-300 mb-2`}>No mods installed</h3>
                        <p css={tw`text-sm max-w-md mx-auto`}>
                            Click &quot;Install Mods&quot; to browse and install mods from our extensive mod database
                        </p>
                    </div>
                ) : (
                    <div css={tw`space-y-3`}>
                        {installedMods.map((mod, index) => (
                            <div
                                key={index}
                                css={tw`bg-neutral-700 p-4 rounded-lg border border-neutral-600 flex justify-between items-center hover:border-neutral-500 transition-colors`}
                            >
                                <div css={tw`flex-1`}>
                                    <h4 css={tw`text-white font-semibold mb-1`}>{mod.name}</h4>
                                    <p css={tw`text-sm text-neutral-400 mb-1`}>
                                        <span css={tw`font-mono text-xs bg-neutral-800 px-2 py-1 rounded mr-2`}>
                                            {mod.fileName}
                                        </span>
                                        <span css={tw`text-neutral-500`}>{(mod.size / 1024 / 1024).toFixed(2)} MB</span>
                                    </p>
                                    {mod.version && (
                                        <p css={tw`text-xs text-neutral-500`}>
                                            <span css={tw`bg-blue-900 text-blue-300 px-2 py-1 rounded text-xs`}>
                                                Version: {mod.version}
                                            </span>
                                        </p>
                                    )}
                                </div>
                                <Button
                                    type={'button'}
                                    size={'small'}
                                    color={'red'}
                                    onClick={() => uninstallMod(mod.fileName)}
                                    css={tw`ml-4`}
                                >
                                    <FontAwesomeIcon icon={faTrash} />
                                </Button>
                            </div>
                        ))}
                    </div>
                )}
            </TitledGreyBox>

            {/* Mod Browser Modal */}
            {showBrowser && (
                <div css={tw`fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4`}>
                    <div css={tw`bg-neutral-800 rounded-lg max-w-4xl w-full max-h-full overflow-hidden flex flex-col`}>
                        {/* Header */}
                        <div css={tw`flex justify-between items-center p-6 border-b border-neutral-700`}>
                            <div>
                                <h2 css={tw`text-xl font-bold text-white mb-2`}>Install New Mods</h2>
                                <p css={tw`text-sm text-neutral-400`}>Browse and install mods from our database</p>
                            </div>
                            <Button type={'button'} size={'small'} onClick={() => setShowBrowser(false)} css={tw`ml-4`}>
                                <FontAwesomeIcon icon={faTimes} />
                            </Button>
                        </div>

                        {/* Search Bar */}
                        <div css={tw`p-6 border-b border-neutral-700`}>
                            <input
                                type='text'
                                placeholder='Search mods...'
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                css={tw`w-full p-3 rounded bg-neutral-700 border border-neutral-600 text-white placeholder-neutral-400`}
                            />
                        </div>

                        {/* Mods List */}
                        <div css={tw`flex-1 overflow-y-auto p-6`}>
                            {filteredMods.length === 0 ? (
                                <div css={tw`text-center py-8 text-neutral-400`}>
                                    <p>No mods found matching &quot;{searchTerm}&quot;</p>
                                </div>
                            ) : (
                                <div css={tw`space-y-4`}>
                                    {filteredMods.map((mod) => (
                                        <div
                                            key={mod.id}
                                            css={tw`bg-neutral-700 p-4 rounded flex justify-between items-start`}
                                        >
                                            <div css={tw`flex-1`}>
                                                <div css={tw`flex items-center mb-2`}>
                                                    {mod.logo_url && (
                                                        <img
                                                            src={mod.logo_url}
                                                            alt={mod.name}
                                                            css={tw`w-8 h-8 rounded mr-3`}
                                                        />
                                                    )}
                                                    <h4 css={tw`text-white font-medium`}>{mod.name}</h4>
                                                </div>
                                                <p css={tw`text-sm text-neutral-400 mb-2`}>{mod.summary}</p>
                                                <div css={tw`flex items-center text-xs text-neutral-500 space-x-4`}>
                                                    <span>{mod.download_count.toLocaleString()} downloads</span>
                                                    {mod.latest_version && (
                                                        <span>Latest: {mod.latest_version.display_name}</span>
                                                    )}
                                                </div>
                                                {mod.categories.length > 0 && (
                                                    <div css={tw`mt-2 flex flex-wrap gap-1`}>
                                                        {mod.categories.slice(0, 3).map((category, idx) => (
                                                            <span
                                                                key={idx}
                                                                css={tw`text-xs px-2 py-1 bg-neutral-600 rounded text-neutral-300`}
                                                            >
                                                                {category}
                                                            </span>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                            <Button
                                                type={'button'}
                                                size={'small'}
                                                color={'primary'}
                                                disabled={!mod.latest_version || installing === mod.id}
                                                onClick={() => installMod(mod)}
                                                css={tw`ml-4`}
                                            >
                                                {installing === mod.id ? (
                                                    <Spinner size={'small'} />
                                                ) : (
                                                    <>
                                                        <FontAwesomeIcon icon={faDownload} css={tw`mr-1`} />
                                                        Install
                                                    </>
                                                )}
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        {/* Footer */}
                        <div css={tw`p-6 border-t border-neutral-700 flex justify-end`}>
                            <Button type={'button'} onClick={() => setShowBrowser(false)}>
                                Close
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default ModManagerContainer;
