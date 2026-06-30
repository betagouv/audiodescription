FROM rustfs/rustfs
USER root
RUN ARCH=$(uname -m) && \
    case "$ARCH" in \
      x86_64)  MC_ARCH="amd64" ;; \
      aarch64) MC_ARCH="arm64" ;; \
      *) echo "Architecture non supportée: $ARCH" && exit 1 ;; \
    esac && \
    curl -fsSL "https://dl.min.io/client/mc/release/linux-${MC_ARCH}/mc" -o /usr/bin/mc && \
    chmod +x /usr/bin/mc
