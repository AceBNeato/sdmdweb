# QR Scanner Test Matrix

Use this checklist to verify QR scanner functionality across devices and configurations.

## Test Environment Setup
- Server configured with HTTPS and a trusted CA certificate
- Test QR codes generated with public HTTPS URLs
- Test devices on the same LAN as the server

## iOS Devices

### Safari (iOS)
| Test | Expected Result | Notes |
|------|----------------|-------|
| Camera permission prompt | Appears and allows | User must tap "Allow" |
| Scanner initializes | Camera feed shown | May require tap to start |
| QR detection | Fast and accurate | Works within 1-2 seconds |
| Fallback on HTTP | Shows setup guide | Clear message with link |
| Public scanner access | Works via URL | Opens equipment details |

### Chrome (iOS)
| Test | Expected Result | Notes |
|------|----------------|-------|
| Camera permission prompt | Appears and allows | Similar to Safari |
| Scanner initializes | Camera feed shown | May be slower than Safari |
| QR detection | Works | Slightly slower than Safari |
| Fallback on HTTP | Shows setup guide | Same as Safari |

### Older iOS (12.x)
| Test | Expected Result | Notes |
|------|----------------|-------|
| Camera API support | May fail | Older devices may not support getUserMedia |
| Fallback UI | Shows setup guide | Graceful degradation |
| Public scanner | Works via URL | Camera app fallback works |

## Android Devices

### Chrome (Android 10+)
| Test | Expected Result | Notes |
|------|----------------|-------|
| Camera permission prompt | Appears and allows | First-time only |
| Scanner initializes | Camera feed shown | Good performance |
| QR detection | Fast and accurate | Works reliably |
| Fallback on HTTP | Shows setup guide | Clear instructions |
| Public scanner access | Works via URL | Opens equipment details |

### Samsung Internet
| Test | Expected Result | Notes |
|------|----------------|-------|
| Camera permission prompt | Appears and allows | Similar to Chrome |
| Scanner initializes | Camera feed shown | May be slower |
| QR detection | Works | Acceptable performance |
| Fallback on HTTP | Shows setup guide | Same as Chrome |

### Older Android (8.x)
| Test | Expected Result | Notes |
|------|----------------|-------|
| Camera API support | May be limited | Some devices support getUserMedia |
| Scanner initializes | May fail | Fallback UI shown |
| QR detection | Unreliable | Use camera app fallback |
| Public scanner | Works via URL | Camera app fallback works |

## Desktop Browsers

### Chrome/Edge (Desktop)
| Test | Expected Result | Notes |
|------|----------------|-------|
| Camera permission prompt | Appears and allows | Requires HTTPS |
| Scanner initializes | Camera feed shown | Good for testing |
| QR detection | Works | Use webcam for testing |
| Fallback on HTTP | Shows setup guide | Clear message |

### Safari (macOS)
| Test | Expected Result | Notes |
|------|----------------|-------|
| Camera permission prompt | Appears and allows | Requires HTTPS |
| Scanner initializes | Camera feed shown | Good performance |
| QR detection | Works | Similar to iOS |

## Network Scenarios

### Correct HTTPS Setup
- **Trusted Certificate**: Scanner works, no browser warnings
- **Camera Access**: Prompt appears, scanner initializes
- **QR Detection**: Fast and reliable

### Untrusted Certificate
- **Browser Warning**: "Your connection is not private"
- **Scanner Fails**: Camera API blocked
- **Fallback UI**: Shows setup guide with certificate steps
- **Workaround**: Advanced users can proceed, but certificate install recommended

### HTTP Access
- **Camera Blocked**: Browser prevents camera access
- **Clear Message**: Explains HTTPS requirement
- **Setup Guide Link**: Points to /public/qr-setup
- **Public Scanner**: Still works via camera app

## Error Handling Tests

### Camera Denied
- **Message**: "Camera access required"
- **Actions**: Setup guide link, retry button
- **Alternative**: Shows public scanner URL

### Camera Busy
- **Message**: Clear error about camera in use
- **Recovery**: Retry button available

### Network Error
- **Message**: Network connectivity issue
- **Recovery**: Retry instructions

## Performance Benchmarks

### Target Metrics
- **Scanner Initialization**: < 3 seconds
- **QR Detection**: < 2 seconds
- **Permission Handling**: Immediate prompt
- **Fallback Display**: < 1 second

### Test Results Template
```
Device: [Device Name]
OS: [iOS/Android Version]
Browser: [Browser/Version]
Network: [WiFi/LAN]
HTTPS: [Yes/No]
Camera Permission: [Granted/Denied]
Scanner Init: [Time]
QR Detection: [Time]
Notes: [Any issues]
```

## Troubleshooting Guide

### Common Issues
1. **Certificate not trusted**: Follow setup guide steps
2. **Camera permission denied**: Check browser settings
3. **Old browser**: Use camera app fallback
4. **Network issues**: Verify LAN connectivity
5. **Wrong hostname**: Use correct LAN URL

### Debug Steps
1. Check browser console for errors
2. Verify certificate installation
3. Test with different browsers
4. Check network connectivity
5. Verify server HTTPS configuration

## Success Criteria
- [ ] Camera permission works on HTTPS
- [ ] QR codes scan reliably
- [ ] Fallback UI shows on HTTP/unsupported
- [ ] Setup guide accessible and helpful
- [ ] Public scanner works via URL
- [ ] Certificate installation instructions clear
- [ ] Error messages are actionable
- [ ] Performance within targets
